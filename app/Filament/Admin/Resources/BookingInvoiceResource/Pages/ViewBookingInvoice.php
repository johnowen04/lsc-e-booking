<?php

namespace App\Filament\Admin\Resources\BookingInvoiceResource\Pages;

use App\Actions\Booking\RepaymentBookingFlow;
use App\Enums\PaymentMethod;
use App\Filament\Admin\Resources\BookingInvoiceResource;
use App\Filament\Admin\Pages\Payment\PaymentStatus as AdminPaymentStatus;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewBookingInvoice extends ViewRecord
{
    protected static string $resource = BookingInvoiceResource::class;

    protected RepaymentBookingFlow $repaymentBookingFlow;

    public function boot(RepaymentBookingFlow $repaymentBookingFlow): void
    {
        $this->repaymentBookingFlow = $repaymentBookingFlow;
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
            $payment = $this->repaymentBookingFlow->execute(
                $data,
                $this->record,
                [
                    'creator' => filament()->auth()->user(),
                    'callback_class' => AdminPaymentStatus::class,
                ]
            );
            
            redirect(AdminPaymentStatus::getUrl(['order_id' => $payment->uuid]));
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Failed to record payment.')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }
}
