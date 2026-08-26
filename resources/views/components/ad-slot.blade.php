@props(['placement'])

@php
    $testMode = config('advertising.test_mode');
    $client = config('advertising.adsense.client');
    $slot = config("advertising.adsense.slots.{$placement}");
    $isConfiguredAdSenseSlot = filled($client) && filled($slot);
    $shouldRender = config('advertising.enabled') && ($testMode || $isConfiguredAdSenseSlot);
    $label = $testMode ? '広告（開発用）' : '広告';
@endphp

@if ($shouldRender)
    <section {{ $attributes->class('bp-ad-slot') }} aria-label="{{ $label }}" data-ad-placement="{{ $placement }}"
        data-ad-mode="{{ $testMode ? 'placeholder' : 'adsense' }}">
        <p class="bp-ad-slot__label">{{ $label }}</p>
        <div class="bp-ad-slot__content">
            @if ($testMode)
                <div class="bp-ad-slot__placeholder" aria-hidden="true">
                    <span>AD PREVIEW</span>
                </div>
            @else
                <ins class="adsbygoogle block" style="display:block" data-ad-client="{{ $client }}"
                    data-ad-slot="{{ $slot }}" data-ad-format="auto" data-full-width-responsive="true"></ins>
            @endif
        </div>
    </section>

    @unless ($testMode)
        <script>
            (adsbygoogle = window.adsbygoogle || []).push({});
        </script>
    @endunless
@endif
