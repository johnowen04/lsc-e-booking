<?php

namespace App\Filament\Admin\Resources\BookingInvoiceResource\Pages;

use App\Enums\PaymentMethod;
use App\Filament\Admin\Resources\BookingInvoiceResource;
use App\Models\Payment;
use App\Services\MidtransService;
use App\Services\PaymentService;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class ViewBookingInvoice extends ViewRecord
{
    protected static string $resource = BookingInvoiceResource::class;

    public function mount($record): void
    {
        parent::mount($record);

        $snapUrl = Cache::pull("snap_admin_{$this->record->id}");

        if ($snapUrl) {
            redirect()->away($snapUrl);
        }

        $redirectUrl = Cache::pull("cash_{$this->record->id}");

        if ($redirectUrl) {
            redirect()->away($redirectUrl);
        }
     }

    public function getHeaderActions(): array
    {
        return [
            Action::make('repayment')
                ->label('Repayment')
                ->icon('heroicon-o-credit-card')
                ->color('success')
                ->disabled($this->record->status !== 'partially_paid')
                ->form([
                    Select::make('payment_method')
                        ->label('Payment Method')
                        ->options(PaymentMethod::toArray())
                        ->required()
                        ->default('cash'),

                    TextInput::make('amount_display')
                        ->label('Amount')
                        ->numeric()
                        ->required()
                        ->readOnly()
                        ->prefix('IDR ')
                        ->default(fn($record) => $record->getRemainingAmount())
                        ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                        ->dehydrated(),

                    Hidden::make('amount')
                        ->default(fn($record) => $record->getRemainingAmount()),
                ])
                ->modalHeading('Record Repayment')
                ->modalSubmitActionLabel('Confirm Payment')
                ->action(fn(array $data) => $this->processRepayment($data)),
            Action::make('print')
                ->label('Print Invoice')
                ->icon('heroicon-o-printer')
                ->openUrlInNewTab(),
        ];
    }

    protected function processRepayment(array $data): void
    {
        if ($this->record->status === 'paid') {
            Notification::make()
                ->title('This invoice has been paid.')
                ->danger()
                ->send();
            return;
        }

        try {
            $payment = app(PaymentService::class)->createPayment(
                amount: $data['amount'],
                invoice: $this->record,
                overrides: $this->getCreatorInfo(),
            );

            if (PaymentMethod::from($data['payment_method']) === PaymentMethod::CASH) {
                $this->handleCashPayment($payment);
            } elseif (PaymentMethod::from($data['payment_method']) === PaymentMethod::QRIS) {
                $this->handleQrisPayment($payment);
            } else {
                throw ValidationException::withMessages([
                    'payment_method' => 'Invalid payment method selected.',
                ]);
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Failed to record payment.')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    protected function handleCashPayment(Payment $payment)
    {
        app(PaymentService::class)->updatePayment(
            $payment->uuid,
            (float) $payment->amount,
            PaymentMethod::CASH->value,
            'paid',
            $this->record,
            paidAt: now()->toDateTimeString(),
            overrides: $this->getCreatorInfo()
        );

        Notification::make()
            ->title('Payment recorded successfully.')
            ->success()
            ->send();

        return redirect(BookingInvoiceResource::getUrl('view', ['record' => $this->record->id]));
    }

    protected function handleQrisPayment(Payment $payment)
    {
        $this->record->load('bookings.court');

        $itemDetails = $this->record->bookings->map(function ($booking) {
            return [
                'id' => $booking->id,
                'price' => (int) round($booking->total_price / 2, -2),
                'quantity' => 1,
                'name' => "Repayment Court {$booking->court->name} ({$booking->starts_at->format('H:i')} - {$booking->ends_at->format('H:i')})",
            ];
        })->toArray();

        $expectedTotal = array_sum(array_column($itemDetails, 'price'));

        $snapUrl = app(MidtransService::class)->generateSnapUrl(
            $payment->uuid,
            $expectedTotal,
            $this->record->customer_name,
            $this->record->customer_phone,
            $itemDetails,
        );

        Cache::put("snap_admin_{$this->record->id}", $snapUrl, now()->addMinutes(5)); //ttl

        Notification::make()
            ->title('Redirecting to payment...')
            ->success()
            ->send();

        redirect()->away($snapUrl)->send();
        return;
    }

    protected function getCreatorInfo(): array
    {
        return [
            'created_by_type' => filament()->auth()->user()::class,
            'created_by_id' => filament()->auth()->id(),
        ];
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }
}
