<div class="space-y-4" x-data="{
    init() {
        window.addEventListener('click', e => {
            if (!this.$el.contains(e.target) &&
                @entangle('selectedCourtId').defer !== null &&
                !e.target.closest('.booking-form-drawer')) {
                @this.call('clearSelection');
            }
        });

        window.addEventListener('bookingCreated', () => {
            @this.call('clearSelection');
        });
    }
}">
    <x-booking.grid.table :rows="$rows" :courts="$courts" :selected-date="$selectedDate" :selected-court-id="$selectedCourtId" :selected-start-hour="$selectedStartHour" />
</div>
