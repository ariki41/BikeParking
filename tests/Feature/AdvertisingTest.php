<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdvertisingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'advertising.enabled' => false,
            'advertising.test_mode' => false,
            'advertising.adsense.client' => null,
            'advertising.adsense.slots.home_footer' => null,
            'advertising.adsense.slots.parking_spot_footer' => null,
            'advertising.adsense.slots.search_footer' => null,
        ]);
    }

    public function test_advertising_is_not_rendered_by_default(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('adsbygoogle.js', false)
            ->assertDontSee('data-ad-placement=', false);

        $this->get(route('search'))
            ->assertOk()
            ->assertDontSee('adsbygoogle.js', false)
            ->assertDontSee('data-ad-placement=', false);

        $this->get('/ads.txt')->assertNotFound();
    }

    public function test_configured_adsense_slot_renders_a_labelled_manual_ad_slot(): void
    {
        config([
            'advertising.enabled' => true,
            'advertising.adsense.client' => 'ca-pub-1234567890123456',
            'advertising.adsense.slots.home_footer' => '1234567890',
        ]);

        $this->view('components.ad-slot', ['placement' => 'home_footer'])
            ->assertSee('広告')
            ->assertSee('data-ad-placement="home_footer"', false)
            ->assertSee('data-ad-client="ca-pub-1234567890123456"', false)
            ->assertSee('data-ad-slot="1234567890"', false);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('adsbygoogle.js?client=ca-pub-1234567890123456', false);

        $this->get(route('privacy'))
            ->assertOk()
            ->assertDontSee('adsbygoogle.js', false);
    }

    public function test_configured_adsense_search_slot_renders_after_the_search_results(): void
    {
        config([
            'advertising.enabled' => true,
            'advertising.adsense.client' => 'ca-pub-1234567890123456',
            'advertising.adsense.slots.search_footer' => '9876543210',
        ]);

        $this->get(route('search'))
            ->assertOk()
            ->assertSee('data-ad-placement="search_footer"', false)
            ->assertSee('data-ad-slot="9876543210"', false)
            ->assertSee('adsbygoogle.js?client=ca-pub-1234567890123456', false);
    }

    public function test_test_mode_renders_a_placeholder_without_loading_adsense(): void
    {
        config([
            'advertising.enabled' => true,
            'advertising.test_mode' => true,
            'advertising.adsense.client' => 'ca-pub-1234567890123456',
            'advertising.adsense.slots.home_footer' => '1234567890',
        ]);

        $this->view('components.ad-slot', ['placement' => 'home_footer'])
            ->assertSee('広告（開発用）')
            ->assertSee('AD PREVIEW')
            ->assertSee('data-ad-placement="home_footer"', false)
            ->assertSee('data-ad-mode="placeholder"', false)
            ->assertDontSee('adsbygoogle')
            ->assertDontSee('data-ad-client=')
            ->assertDontSee('data-ad-slot=');

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('adsbygoogle.js', false);

        $this->get(route('search'))
            ->assertOk()
            ->assertSee('data-ad-placement="search_footer"', false)
            ->assertSee('AD PREVIEW')
            ->assertDontSee('adsbygoogle.js', false)
            ->assertDontSee('data-ad-client=', false);
    }

    public function test_test_mode_is_not_rendered_until_advertising_is_enabled(): void
    {
        config([
            'advertising.enabled' => false,
            'advertising.test_mode' => true,
        ]);

        $this->view('components.ad-slot', ['placement' => 'home_footer'])
            ->assertDontSee('data-ad-placement=', false)
            ->assertDontSee('AD PREVIEW');
    }

    public function test_ads_txt_uses_the_configured_adsense_publisher_id(): void
    {
        config([
            'advertising.enabled' => true,
            'advertising.adsense.client' => 'ca-pub-1234567890123456',
        ]);

        $this->get('/ads.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('google.com, pub-1234567890123456, DIRECT, f08c47fec0942fa0');
    }

    public function test_advertising_and_privacy_page_is_available(): void
    {
        $this->get(route('privacy'))
            ->assertOk()
            ->assertSee('広告とプライバシー')
            ->assertSee('Google AdSense');
    }
}
