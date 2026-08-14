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
- 駐輪場画像のアップロード（WebP変換）
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
```

Laravel Sailのショートカットを利用できる環境では、上記の `docker compose exec laravel.test` を `./vendor/bin/sail` に置き換えられます。

料金表示・入力・保存に関する変更では、まず次のfocused testを実行してください。

```bash
./vendor/bin/sail test tests/Feature/ParkingSpotRateDisplayTest.php
```

## ドキュメント

- [簡易設計書](doc/design.md)
- [状態遷移図](doc/state-transition.md)

設計書と状態遷移図はMarkdownとMermaidで管理しているため、GitHub上またはMermaid対応エディタで確認できます。

## ディレクトリ構成

```text
app/
├── Http/Controllers/     HTTPリクエストと画面遷移
├── Http/Requests/        入力値検証
├── Livewire/             住所補完などのリアクティブ処理
├── Models/               Eloquentモデル
└── Services/              駐輪場の保存・更新などの業務処理
database/                 マイグレーション、ファクトリ、シーダー
resources/views/          Bladeテンプレート
routes/                   Web・認証ルート
tests/                    Feature / Unitテスト
doc/                      設計書・状態遷移図
```

## ライセンス

このプロジェクトはMITライセンスで公開されています。
