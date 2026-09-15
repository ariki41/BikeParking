<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'contact.form_url' => 'https://forms.example.test/contact',
            'contact.email' => 'support@example.test',
        ]);
    }

    public function test_legal_pages_are_available_from_the_footer(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee(route('terms'), false)
            ->assertSee(route('privacy'), false)
            ->assertSee(route('contact'), false);
    }

    public function test_terms_explains_collaborative_editing_and_moderation(): void
    {
        $this->get(route('terms'))
            ->assertOk()
            ->assertSee('共同編集の責任')
            ->assertSee('禁止行為')
            ->assertSee('掲載停止・削除');
    }

    public function test_privacy_policy_explains_user_content_cookies_and_account_deletion(): void
    {
        $this->get(route('privacy'))
            ->assertOk()
            ->assertSee('アカウント情報')
            ->assertSee('登録した駐輪場・料金・画像、投稿したレビュー、更新履歴')
            ->assertSee('Google AdSense')
            ->assertSee('退会後の扱い');
    }

    public function test_contact_page_has_a_configurable_google_form(): void
    {
        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('お問い合わせフォーム')
            ->assertSee('https://forms.example.test/contact', false)
            ->assertSee('support@example.test')
            ->assertSee('通報機能');
    }
}
