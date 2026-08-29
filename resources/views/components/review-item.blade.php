@props(['review'])

<article {{ $attributes->class(['py-5']) }}>
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
            <p class="font-semibold text-slate-800">{{ $review->user?->name ?? '退会済みユーザー' }}</p>
            <p class="mt-1 text-amber-500" aria-label="5段階中{{ $review->rating }}の評価">
                <span aria-hidden="true">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
            </p>
        </div>
        <time class="text-xs text-slate-500" datetime="{{ $review->updated_at?->toIso8601String() }}">
            {{ $review->updated_at?->format('Y-m-d H:i') }}
        </time>
    </div>
    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $review->comment }}</p>
</article>
