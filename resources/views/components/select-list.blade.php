<select
    class="form-control rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
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
