<x-app-layout title="駐輪場を登録" robots="noindex, nofollow">
    <x-parking-spot-form action="{{ route('parking_spot.confirm') }}" :capacity="$capacity"
        :displacement-classes="$displacementClasses" :form-values="$formValues" :image-paths="$imagePaths"
        mode="create" :rate-day-types="$rateDayTypes"
        :rate-unit-minutes="$rateUnitMinutes" :max-rate-periods="$maxRatePeriods" :rates-input="$ratesInput" :business-hour-day-types="$businessHourDayTypes" :business-hours-input="$businessHoursInput" />
</x-app-layout>
