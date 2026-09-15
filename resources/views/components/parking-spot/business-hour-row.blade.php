@props(['index', 'hour', 'businessHourDayTypes', 'messages' => [], 'template' => false])
@php($namePrefix = $template ? null : "business_hours[{$index}]")
<div class="business-hour-item mb-3 rounded-lg border border-slate-200 bg-white p-4" data-business-hour-item>
    <div class="mb-3 flex items-center justify-between gap-3"><h3 class="font-semibold text-slate-800">営業時間<span class="business-hour-number">{{ $template ? '' : $index + 1 }}</span></h3><button class="bp-danger-link" data-delete-business-hour type="button">削除</button></div>
    @foreach ($messages as $fieldMessages) @foreach ($fieldMessages as $message)<p class="mb-2 text-sm text-red-600">{{ $message }}</p>@endforeach @endforeach
    <div class="grid gap-4 md:grid-cols-3">
        <div><x-input-label>曜日区分</x-input-label><select class="bp-select" data-business-hour-field="day_type" @if($namePrefix) name="{{ $namePrefix }}[day_type]" @endif>@foreach($businessHourDayTypes as $key => $label)<option value="{{ $key }}" @selected(($hour['day_type'] ?? '全日') === $key)>{{ $label }}</option>@endforeach</select></div>
        <div><x-input-label>開場時間</x-input-label><input class="bp-input business-hour-time" data-business-hour-field="opening_time" type="time" value="{{ $hour['opening_time'] ?? '00:00' }}" @if($namePrefix) name="{{ $namePrefix }}[opening_time]" @endif></div>
        <div><x-input-label>閉場時間</x-input-label><input class="bp-input business-hour-time" data-business-hour-field="closing_time" type="time" value="{{ $hour['closing_time'] ?? '00:00' }}" @if($namePrefix) name="{{ $namePrefix }}[closing_time]" @endif></div>
    </div>
    <label class="mt-3 flex items-center gap-2 text-sm text-slate-700"><input data-business-hour-field="is_closed" class="business-hour-closed rounded border-slate-300 text-emerald-600" type="checkbox" value="1" @checked($hour['is_closed'] ?? false) @if($namePrefix) name="{{ $namePrefix }}[is_closed]" @endif> この時間帯は休業（00:00〜00:00 は終日休業）</label>
</div>
