<div data-business-hours data-max-business-hours="8">
    <div class="mb-3 flex justify-end"><button class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700" data-add-business-hour type="button">営業時間を追加</button></div>
    @error('business_hours')<p class="mb-2 text-sm text-red-600">{{ $message }}</p>@enderror
    <div data-business-hour-list>@foreach($businessHoursInput as $index => $hour) @php($messages = collect($errors->getMessages())->filter(fn($messages, $field) => str_starts_with($field, "business_hours.{$index}."))) <x-parking-spot.business-hour-row :index="$index" :hour="$hour" :business-hour-day-types="$businessHourDayTypes" :messages="$messages" /> @endforeach</div>
    <template data-business-hour-template><x-parking-spot.business-hour-row :index="0" :hour="['day_type' => '平日', 'is_closed' => false, 'opening_time' => '00:00', 'closing_time' => '00:00']" :business-hour-day-types="$businessHourDayTypes" template /></template>
</div>
