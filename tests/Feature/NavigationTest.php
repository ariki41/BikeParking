<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_navigation_shows_public_links_without_authenticated_links(): void
    {
        $response = $this->get(route('home'))->assertOk();

        $this->assertNavigationLinkCount($response->getContent(), route('home'), 'ホーム', 2);
        $this->assertNavigationLinkCount($response->getContent(), route('login'), 'ログイン', 2);
        $this->assertNavigationLinkCount($response->getContent(), route('register'), '新規登録', 2);
        $this->assertNavigationLinkCount($response->getContent(), route('favorites.index'), 'お気に入り (0)', 0);
        $this->assertNavigationLinkCount($response->getContent(), route('profile.edit'), 'マイページ', 0);
    }

    public function test_authenticated_navigation_shows_primary_links_on_desktop_and_mobile(): void
    {
        $user = User::factory()->create([
            'name' => 'ナビゲーションユーザー',
        ]);

        $response = $this->actingAs($user)
            ->get(route('home'))
            ->assertOk();

        $html = $response->getContent();

        $this->assertNavigationLinkCount($html, route('home'), 'ホーム', 2);
        $this->assertNavigationLinkCount($html, route('favorites.index'), 'お気に入り (0)', 2);
        $this->assertNavigationLinkCount($html, route('profile.edit'), 'マイページ', 2);
        $this->assertNavigationLinkCount($html, route('login'), 'ログイン', 0);
        $this->assertNavigationLinkCount($html, route('register'), '新規登録', 0);
        $this->assertCount(2, $this->navigationElements($html, '//*[@aria-label="ログイン中のユーザー"]'));
        $this->assertCount(2, $this->navigationElements($html, '//form[@action="'.route('logout').'"]/button[@type="submit"]'));
    }

    #[DataProvider('activeNavigationLinkProvider')]
    public function test_primary_navigation_marks_the_current_screen_as_active(string $routeName, string $label): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route($routeName))
            ->assertOk();

        $links = $this->navigationLinks($response->getContent(), route($routeName), $label);

        $this->assertCount(2, $links);
        $this->assertTrue($this->hasActiveDesktopLink($links));
        $this->assertTrue($this->hasActiveMobileLink($links));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function activeNavigationLinkProvider(): array
    {
        return [
            'home' => ['home', 'ホーム'],
            'favorites' => ['favorites.index', 'お気に入り (0)'],
            'profile' => ['profile.edit', 'マイページ'],
        ];
    }

    private function assertNavigationLinkCount(string $html, string $href, string $label, int $expectedCount): void
    {
        $this->assertCount($expectedCount, $this->navigationLinks($html, $href, $label));
    }

    /**
     * @return list<DOMElement>
     */
    private function navigationLinks(string $html, string $href, string $label): array
    {
        return collect($this->navigationElements($html, '//a[@href="'.$href.'"]'))
            ->filter(fn (DOMElement $link): bool => trim($link->textContent) === $label)
            ->values()
            ->all();
    }

    /**
     * @return list<DOMElement>
     */
    private function navigationElements(string $html, string $query): array
    {
        $document = new DOMDocument;
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $elements = (new DOMXPath($document))->query('//nav'.$query);

        return $elements === false ? [] : iterator_to_array($elements);
    }

    /**
     * @param  list<DOMElement>  $links
     */
    private function hasActiveDesktopLink(array $links): bool
    {
        return collect($links)->contains(
            fn (DOMElement $link): bool => str_contains($link->getAttribute('class'), 'border-b-2')
                && str_contains($link->getAttribute('class'), 'border-emerald-500'),
        );
    }

    /**
     * @param  list<DOMElement>  $links
     */
    private function hasActiveMobileLink(array $links): bool
    {
        return collect($links)->contains(
            fn (DOMElement $link): bool => str_contains($link->getAttribute('class'), 'border-l-4')
                && str_contains($link->getAttribute('class'), 'border-emerald-500'),
        );
    }
}
