<?php

namespace App\Livewire;

use Carbon\Carbon;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class BookingDateNavigator extends Component
{
    #[Modelable]
    public $selectedDate;
    public Carbon $baseDate;
    public int $daysShown;
    public array $tabDates = [];
    public string $activeTabDate;
    public string $quickPickedDate;
    public Carbon $minDate;
    public ?Carbon $maxDate = null;

    public function mount($selectedDate = null, $daysShown = 7, $minDate = null, $maxDate = null)
    {
        $this->daysShown = $daysShown;
        $this->minDate = $minDate ? Carbon::parse($minDate)->startOfDay() : now()->startOfDay();
        $this->maxDate = $maxDate ? Carbon::parse($maxDate)->startOfDay() : null;

        $initialDate = Carbon::parse($selectedDate ?? now()->toDateString());

        $this->baseDate = $initialDate->copy();
        $this->activeTabDate = $initialDate->toDateString();
        $this->quickPickedDate = $initialDate->toDateString();
        $this->selectedDate = $initialDate->toDateString();

        $this->updateTabDates();
    }

    public function updateTabDates()
    {
        $this->tabDates = collect(range(0, $this->daysShown - 1))
            ->map(fn($i) => $this->baseDate->copy()->addDays($i)->toDateString())
            ->filter(fn($date) => $this->maxDate === null || Carbon::parse($date)->lte($this->maxDate))
            ->values()
            ->toArray();
    }

    public function previousRange()
    {
        $newBase = $this->baseDate->copy()->subDays($this->daysShown);

        if ($newBase->lt($this->minDate)) {
            return;
        }

        $this->baseDate = $newBase;
        $this->updateTabDates();

        $pickedStr = $this->baseDate->toDateString();

        if (in_array($pickedStr, $this->tabDates)) {
            $this->selectTab($pickedStr);
        } elseif (!empty($this->tabDates)) {
            $this->selectTab($this->tabDates[0]);
        }
    }

    public function nextRange()
    {
        $newBase = $this->baseDate->copy()->addDays($this->daysShown);

        if ($this->maxDate && $newBase->gte($this->maxDate)) {
            return;
        }

        $this->baseDate = $newBase;
        $this->updateTabDates();

        $pickedStr = $this->baseDate->toDateString();

        if (in_array($pickedStr, $this->tabDates)) {
            $this->selectTab($pickedStr);
        } elseif (!empty($this->tabDates)) {
            $this->selectTab($this->tabDates[0]);
        }
    }

    public function selectTab(string $date)
    {
        $this->activeTabDate = $date;
        $this->quickPickedDate = $date;
        $this->selectedDate = $date;
    }

    public function updatedQuickPickedDate($value)
    {
        $picked = Carbon::parse($value);
        $this->baseDate = $picked->copy();
        $this->updateTabDates();

        $pickedStr = $picked->toDateString();

        if (in_array($pickedStr, $this->tabDates)) {
            $this->selectTab($pickedStr);
        } elseif (!empty($this->tabDates)) {
            $this->selectTab($this->tabDates[0]);
        }
    }

    public function getFormattedRangeProperty(): string
    {
        $start = $this->baseDate->format('j M');
        $end = Carbon::parse(end($this->tabDates))->format('j M Y');
        return "Booking For: {$start} – {$end}";
    }

    public function getIsPreviousDisabledProperty(): bool
    {
        return $this->baseDate->copy()->subDays($this->daysShown)->lt($this->minDate);
    }

    public function getIsNextDisabledProperty(): bool
    {
        if (!$this->maxDate) {
            return false;
        }

        $nextBase = $this->baseDate->copy()->addDays($this->daysShown);
        return $nextBase->gte($this->maxDate);
    }

    public function getFormattedTabDatesProperty(): array
    {
        return collect($this->tabDates)->map(fn($date) => [
            'date' => $date,
            'label' => Carbon::parse($date)->format('D, d M'),
            'isActive' => $this->activeTabDate === $date,
        ])->toArray();
    }

    public function goToToday()
    {
        $today = now()->toDateString();

        $this->baseDate = now()->copy();
        $this->updateTabDates();

        if (in_array($today, $this->tabDates)) {
            $this->selectTab($today);
        } elseif (!empty($this->tabDates)) {
            $this->selectTab($this->tabDates[0]);
        }
    }

    public function getIsTodayDisabledProperty(): bool
    {
        return $this->activeTabDate === now()->toDateString();
    }

    public function render()
    {
        return view('livewire.booking-date-navigator');
    }
}
