import { expect, test } from '@playwright/test';
import { installLeafletMock } from './leaflet-mock.js';

test.beforeEach(async ({ page }) => {
    await installLeafletMock(page);
    await page.route('https://unpkg.com/**', (route) => route.fulfill({ body: '' }));
    await page.route('https://*.tile.openstreetmap.org/**', (route) => route.fulfill({ status: 204 }));
});

test('地図の初期化、マーカー更新、移動後のURL復元と検索結果ページ切替', async ({ page }) => {
    await page.goto('/search?lat=35.68&lon=139.76&zoom=15');

    await expect(page.locator('[data-leaflet-map]')).toBeVisible();
    await expect(page.locator('[data-leaflet-map-error]')).toBeHidden();
    await expect(page.locator('#parking-spots')).toContainText('E2E 駐輪場 01');
    await expect.poll(() => page.evaluate(() => window.__e2eLeafletMaps.length)).toBe(1);
    await expect.poll(() => page.evaluate(() => window.__e2eLeafletMarkerCount())).toBeGreaterThan(0);

    await page.evaluate(() => window.__e2eLeafletMaps[0].fire('moveend'));
    await expect.poll(() => new URL(page.url()).searchParams.get('lat')).toBe('35.68');
    await expect.poll(() => new URL(page.url()).searchParams.get('lon')).toBe('139.76');
    await expect.poll(() => new URL(page.url()).searchParams.get('zoom')).toBe('15');

    await page.getByRole('button', { name: '次へ' }).click();
    await expect(page.locator('#parking-spots')).toContainText('E2E 駐輪場 51');
    await expect.poll(() => new URL(page.url()).searchParams.get('page')).toBe('2');

    await page.reload();
    await expect(page.getByText('2 / 2ページ')).toBeVisible();
    await expect(page.locator('#parking-spots')).toContainText('E2E 駐輪場 51');
});

test('画像、料金帯、営業時間フォームをブラウザで操作し、確認画面から入力を復元する', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('ユーザーID').fill('e2e-user');
    await page.getByLabel('パスワード').fill('password');
    await page.getByRole('button', { name: 'ログイン' }).click();
    await page.goto('/parking-spots/create');

    await page.locator('#images').setInputFiles([
        { name: 'one.png', mimeType: 'image/png', buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL8WAAAAABJRU5ErkJggg==', 'base64') },
        { name: 'two.png', mimeType: 'image/png', buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL8WAAAAABJRU5ErkJggg==', 'base64') },
    ]);
    await expect(page.locator('[data-image-preview-item]')).toHaveCount(2);
    await page.getByRole('button', { name: '画像2を削除' }).click();
    await expect(page.locator('[data-image-preview-item]')).toHaveCount(1);

    await page.getByRole('button', { name: '料金帯を追加' }).click();
    await expect(page.locator('[data-rate-item]')).toHaveCount(2);
    await page.locator('[data-rate-item]').nth(1).locator('.rate-input').fill('250');
    await page.locator('[data-rate-item]').nth(1).getByText('最大料金なし').click();
    await expect(page.locator('[data-rate-item]').nth(1).locator('.max-rate-input')).toBeDisabled();
    await page.locator('[data-rate-item]').nth(1).getByRole('button', { name: '削除' }).click();
    await expect(page.locator('[data-rate-item]')).toHaveCount(1);

    await page.getByRole('button', { name: '営業時間を追加' }).click();
    await expect(page.locator('[data-business-hour-item]')).toHaveCount(2);
    await page.locator('[data-business-hour-item]').nth(1).locator('.business-hour-time').first().fill('09:00');

    await page.locator('#name').fill('E2E 入力復元駐輪場');
    await page.locator('#postalcode').fill('1000001');
    await page.locator('#address1').evaluate((input) => { input.value = '東京都千代田区'; });
    await page.locator('#address2').fill('千代田1-1');
    await page.locator('#capacity').selectOption('1');
    await page.locator('#max_displacement_class').selectOption({ index: 1 });
    await page.locator('.rate-input').first().fill('100');
    await page.getByRole('button', { name: '確認画面へ進む' }).click();

    await expect(page.getByText('E2E 入力復元駐輪場')).toBeVisible();
    await page.getByRole('button', { name: '戻る' }).click();
    await expect(page.locator('#name')).toHaveValue('E2E 入力復元駐輪場');
    await expect(page.locator('[data-image-preview-item]')).toHaveCount(1);
    await expect(page.locator('[data-business-hour-item]')).toHaveCount(2);
});
