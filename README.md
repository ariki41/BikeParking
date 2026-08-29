# Bike Parking

駐輪場の情報を登録・検索・参照するためのLaravel製Webアプリケーションです。
利用者は会員登録・ログイン後、駐輪場の基本情報、営業時間、料金帯、画像を登録・編集できます。

## 主な機能

- 駐輪場の一覧表示・検索
- 駐輪場情報の登録・編集・詳細表示
- 駐輪場のお気に入り追加・解除・一覧表示
- 駐輪場の評価・レビュー投稿
- 郵便番号による住所補完
- 住所からの緯度・経度取得と地図表示
- 曜日・時間帯ごとの料金設定
- 料金帯の重複チェック
- 駐輪場画像のアップロード（最大4枚・WebP変換）
- 駐輪場・料金・画像を一体として保存するトランザクションと画像補償処理
- 確認画面の改ざん・期限切れ対策と放置された一時画像の定期削除
- ユーザーIDによる会員登録・ログイン、プロフィール管理

## 技術構成

| 項目 | 採用技術 |
| --- | --- |
| Backend | PHP 8.3 / Laravel 13 |
| Frontend | Blade / Livewire 3 / Tailwind CSS / Alpine.js |
| Database | MySQL 8.0（SQLiteも利用可能） |
| Development environment | Laravel Sail / Docker Compose |
| Asset build | Vite |

## 必要な環境

- Docker Desktop または Docker Engine
- Docker Compose
- PHP 8.3 / Composer
- Node.js / npm（ローカルでViteを実行する場合）

## セットアップ

### 1. リポジトリの取得

```bash
git clone <repository-url>
cd bike-parking
```

### 2. Laravel Sailの起動

```bash
cp .env.example .env
composer install
docker compose up -d
docker compose exec laravel.test php artisan key:generate
docker compose exec laravel.test php artisan migrate --seed
```

画像や静的ファイルを利用する場合は、必要に応じてストレージリンクも作成します。

```bash
docker compose exec laravel.test php artisan storage:link
```

### 3. フロントエンドの準備

```bash
npm install
npm run dev
```

本番用アセットを生成する場合は `npm run build` を実行してください。

### 4. アプリケーションへのアクセス

ブラウザで [http://localhost](http://localhost) を開きます。
認証にはメールアドレスではなくユーザーIDを使用します。メール確認とメール経由のパスワード再設定は提供していません。

## 環境変数

基本設定は `.env.example` を使用します。住所検索・ジオコード機能を利用する場合は、利用するYOLP APIの情報を追加してください。

```dotenv
YOLP_URL=https://map.yahooapis.jp/search/local/V1/localSearch
YOLP_GEOCODE_URL=https://map.yahooapis.jp/geocode/V1/geoCoder
YOLP_CLIENT_ID=<your-client-id>
```

YOLP設定は `config/services.php` を通して共通APIクライアントから参照されます。検索と駐輪場登録のAPI通信には、共通のタイムアウト・リトライ・レスポンス変換が適用されます。

### 広告（Google AdSense）

広告は初期状態で無効です。手動で配置するのは、ホームの新着一覧の後、駐輪場詳細の補足情報の後、検索結果一覧の直後だけです。検索画面では検索操作・検索結果のカード・地図を覆わず、一覧とサイトフッターの間に表示します。追従表示・全画面表示・自動挿入は使用せず、フォーム・ログイン画面には表示しません。

AdSense の審査完了後に、発行されたサイト運営者 ID と各広告ユニットのスロット ID を設定してください。

```dotenv
ADVERTISING_ENABLED=true
ADSENSE_CLIENT=ca-pub-1234567890123456
ADSENSE_SLOT_HOME_FOOTER=1234567890
ADSENSE_SLOT_PARKING_SPOT_FOOTER=0987654321
ADSENSE_SLOT_SEARCH_FOOTER=9876543210
```

開発環境で広告枠の余白・レスポンシブ表示だけを確認する場合は、実AdSenseへ通信しないプレースホルダーを使用できます。`ADSENSE_*` の設定は不要です。

```dotenv
ADVERTISING_ENABLED=true
ADVERTISING_TEST_MODE=true
```

この設定では「広告（開発用）」と `AD PREVIEW` を表示し、AdSense スクリプトや広告リクエストは出力しません。本番で実広告を配信する際は `ADVERTISING_TEST_MODE=false` にしてください。

設定後は本番サイトの `/ads.txt` で AdSense 用のレコードが返ることも確認してください。広告を有効化する前に、`/privacy` の内容が実際の広告配信事業者と利用者の地域に必要な同意要件に合っていることを確認してください。

データベース接続をMySQLにする場合は、`.env` の `DB_*` をDocker Composeの設定に合わせます。

```dotenv
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=bike_parking
DB_USERNAME=sail
DB_PASSWORD=password
```

## 開発コマンド

```bash
# Laravelコンテナの起動・停止
docker compose up -d
docker compose down

# マイグレーション
docker compose exec laravel.test php artisan migrate

# コード整形
docker compose exec laravel.test vendor/bin/pint

# テスト全体
docker compose exec laravel.test php artisan test

# 24時間以上経過した確認画面用の一時画像を削除
docker compose exec laravel.test php artisan parking-spots:prune-temporary-images --hours=24

# 日本郵便から最新の郵便番号データを取得して同期
docker compose exec laravel.test php artisan postal-codes:sync
```

Laravel Sailのショートカットを利用できる環境では、上記の `docker compose exec laravel.test` を `./vendor/bin/sail` に置き換えられます。
一時画像の削除コマンドはLaravelのスケジューラにも1時間ごとで登録されています。本番・開発サーバーではスケジューラプロセスを常時実行してください。

郵便番号同期は、日本郵便が公開する1レコード1行のUTF-8版ZIPをダウンロードし、内容を検証してから都道府県・市区町村・郵便番号をトランザクション内で更新します。`storage/app/private/x-ken-all.csv` の手動配置は不要です。廃止された郵便番号は、既存の駐輪場との関連を保つため削除せず無効化します。同期コマンドは毎月2日3時にも自動実行されます。ダウンロード元を変更する場合だけ `JAPAN_POST_POSTAL_CODE_URL` を設定してください。

料金表示・入力・保存に関する変更では、まず次のfocused testを実行してください。

```bash
./vendor/bin/sail test tests/Feature/ParkingSpotRateDisplayTest.php
```

## ドキュメント

- [簡易設計書](doc/design.md)
- [状態遷移図](doc/state-transition.md)
- [開発サーバーへのデプロイ](doc/deployment.md)

設計書と状態遷移図はMarkdownとMermaidで管理しているため、GitHub上またはMermaid対応エディタで確認できます。

## ディレクトリ構成

```text
app/
├── Domain/               料金帯などのドメインルール
├── Http/Controllers/     HTTPリクエストと画面遷移
├── Http/Requests/        入力値検証
├── Livewire/             住所補完などのリアクティブ処理
├── Models/               Eloquentモデル
├── Services/             保存更新・ジオコード・画像などの業務処理
└── ValueObjects/         サービス間で受け渡す型付き処理結果
database/                 マイグレーション、ファクトリ、シーダー
resources/views/          Bladeテンプレート
routes/                   Web・認証ルート
tests/                    Feature / Unitテスト
doc/                      設計書・状態遷移図
```

## ライセンス

このプロジェクトはMITライセンスで公開されています。
