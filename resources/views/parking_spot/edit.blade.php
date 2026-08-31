<x-app-layout>
    <x-parking-spot-form action="{{ route('parking_spot.confirm') }}" :capacity="$capacity"
        :form-values="$formValues" :image-paths="$imagePaths" mode="edit" :parking-spot-id="$parkingSpot->id"
        :rate-day-types="$rateDayTypes" :rate-unit-minutes="$rateUnitMinutes" :rates-input="$ratesInput" />
</x-app-layout>
