@props(['placement'])

@php
    $client = config('advertising.adsense.client');
    $slot = config("advertising.adsense.slots.{$placement}");
@endphp

@if (config('advertising.enabled') && filled($client) && filled($slot))
    <section {{ $attributes->class('bp-ad-slot') }} aria-label="広告" data-ad-placement="{{ $placement }}">
        <p class="bp-ad-slot__label">広告</p>
        <div class="bp-ad-slot__content">
            <ins class="adsbygoogle block" style="display:block" data-ad-client="{{ $client }}"
                data-ad-slot="{{ $slot }}" data-ad-format="auto" data-full-width-responsive="true"></ins>
        </div>
    </section>

    <script>
        (adsbygoogle = window.adsbygoogle || []).push({});
    </script>
@endif
