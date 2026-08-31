<div>
    <div class="space-y-3 p-3">
        @foreach ($spots as $spot)
            <article class="bp-card-link overflow-hidden">
                <div class="flex gap-3 p-3">
                    <a class="shrink-0" href="{{ route('parking_spot.show', $spot->id) }}">
                        <img class="h-24 w-28 rounded-md object-cover" src="{{ $spot->image_url }}" alt="駐輪場画像">
                    </a>

                    <div class="min-w-0 flex-1">
                        <a class="block truncate text-base font-bold text-slate-900 hover:text-emerald-700"
                            href="{{ route('parking_spot.show', $spot->id) }}" data-longitude="{{ $spot->longitude }}"
                            data-latitude="{{ $spot->latitude }}">
                            {{ $spot->name }}
                        </a>
                        <p class="mt-1 text-sm leading-5 text-slate-600">{{ $spot->address }}</p>
                        <x-rating-summary class="mt-2" :parking-spot="$spot" />

                        <x-rate-summary class="mt-3" :parking-spot="$spot" />

                        @auth
                            <div class="mt-3">
                                <x-favorite-button :parking-spot="$spot" :favorited="$spot->is_favorited" compact />
                            </div>
                        @endauth
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</div>
