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

    public $groupedSlots;
    public $cartTotal;
    public ?array $data = [];

    protected CreateBookingFlow $createBookingFlow;

    public function boot(CreateBookingFlow $createBookingFlow): void
    {
        $this->createBookingFlow = $createBookingFlow;
    }

    public function mount(): void
    {
        $this->fillFromCart();
        $this->form->fill();
    }

    protected function getListeners(): array
    {
        return [
            'slotsAddedToCart' => 'fillFromCart',
            'cartItemRemoved' => 'fillFromCart'
        ];
    }

    public function fillFromCart(): void
    {
        $this->groupedSlots = $this->getGroupedSlots();
        $this->cartTotal = $this->calculateCartTotal();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('customer_name')
                    ->label('Customer Name')
                    ->required(),

                TextInput::make('customer_phone')
                    ->label('Phone Number')
                    ->tel()
                    ->required(),

                Checkbox::make('is_paid_in_full')
                    ->label('Paid in Full')
                    ->default(true)
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
            $customerPhone = $data['customer_phone'];
            $customer = Customer::where('phone', $customerPhone)->first();

            $payment = $this->createBookingFlow->execute(
                $data,
                $this->getGroupedSlots()->toArray(),
                $customer,
                [
                    'created_by_type' => filament()->auth()->user() ? filament()->auth()->user()::class : null,
                    'created_by_id' => filament()->auth()->id(),
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
