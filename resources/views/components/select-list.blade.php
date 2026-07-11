<select
    class="bp-select"
    id="{{ $name }}" name="{{ $name }}">
    @if ($default !== '')
        <option value="" disabled {{ empty($selected) ? 'selected' : '' }}>{{ $default }}</option>
    @endif
    @foreach ($options as $key => $value)
        <option value="{{ $key }}" {{ $selected == $key ? 'selected' : '' }}>
            {{ $value }}
        </option>
    @endforeach
</select>
