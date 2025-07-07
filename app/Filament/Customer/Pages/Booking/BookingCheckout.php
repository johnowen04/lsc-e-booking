<?php

namespace App\Filament\Customer\Pages\Booking;

use App\Actions\Booking\CreateBookingFlow;
use App\Enums\PaymentMethod;
use App\Filament\Customer\Pages\Payment\PaymentStatus as CustomerPaymentStatus;
use App\Models\Customer;
use App\Traits\InteractsWithBookingCart;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;

class BookingCheckout extends Page
{
    use InteractsWithBookingCart;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string $view = 'filament.customer.pages.booking.booking-checkout';

    protected static bool $shouldRegisterNavigation = false;

    public $cartTotal;

    public ?array $data = [];

    public ?Customer $customer = null;

    protected CreateBookingFlow $createBookingFlow;

    public function boot(CreateBookingFlow $createBookingFlow): void
    {
        $this->createBookingFlow = $createBookingFlow;
    }

    public function mount(): void
    {
        $this->updateTotal();
        $this->customer = filament()->auth()?->user();
        $this->form->fill();
    }

    protected function getListeners(): array
    {
        return [
            'slotsAddedToCart' => 'updateTotal',
            'cartItemRemoved' => 'updateTotal'
        ];
    }

    public function updateTotal(): void
    {
        $this->cartTotal = $this->calculateCartTotal();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Checkbox::make('autofill')
                    ->label('Autofill')
                    ->default(fn() => $this->customer !== null)
                    ->live()
                    ->hidden(fn() => $this->customer === null),

                TextInput::make('customer_name')
                    ->label('Customer Name')
                    ->required()
                    ->default(fn() => $this->customer?->name)
                    ->disabled(fn(Get $get) => $get('autofill'))
                    ->dehydrated(),

                TextInput::make('customer_email')
                    ->label('Customer Email')
                    ->email()
                    ->required()
                    ->default(fn() => $this->customer?->email)
                    ->disabled(fn(Get $get) => $get('autofill'))
                    ->dehydrated(),

                // TextInput::make('customer_phone')
                //     ->label('Phone Number')
                //     ->tel()
                //     ->required()
                //     ->default(fn() => $this->customer?->phone)
                //     ->disabled(fn(Get $get) => $get('autofill'))
                //     ->dehydrated(),

                Checkbox::make('is_paid_in_full')
                    ->label('Paid in Full')
                    ->default(false)
                    ->live(),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        try {
            $this->checkBookingConflicts();
            $data = $this->form->getState();
            $data['payment_method'] = PaymentMethod::QRIS->value;
            $data['customer_id'] = $this->customer?->id ?? null;

            $payment = $this->createBookingFlow->execute(
                $data,
                $this->getGroupedSlots(),
                [
                    'creator' => filament()->auth()->user(),
                    'is_walk_in' => false,
                    'callback_class' => CustomerPaymentStatus::class,
                ]
            );

            $this->clearBookingCart();
            redirect(CustomerPaymentStatus::getUrl(['order_id' => $payment->uuid]));
        } catch (\Throwable $th) {
            Log::error('Booking creation failed', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            Notification::make()
                ->title('Booking failed')
                ->body('Something went wrong while creating the booking. Please try again or contact admin.')
                ->danger()
                ->persistent()
                ->send();

            throw $th;
        }
    }
}
