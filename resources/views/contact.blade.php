<x-app-layout>
    <div class="bp-shell max-w-3xl">
        <article class="bp-panel p-6 sm:p-8">
            <h1 class="text-3xl font-bold text-slate-900">お問い合わせ</h1>
            <p class="mt-4 text-sm leading-7 text-slate-700">BikeParking運営へのお問い合わせは、内容に応じて以下の窓口をご利用ください。</p>

            <section class="mt-8">
                <h2 class="text-xl font-semibold text-slate-900">お問い合わせフォーム</h2>
                <p class="mt-3 text-sm leading-7 text-slate-700">機能の不具合、改善のご提案、利用規約またはプライバシーポリシーに関するお問い合わせは、Googleフォームからお送りください。</p>
                @if (filled(config('contact.form_url')))
                    <a class="mt-4 inline-flex min-h-10 items-center rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2" href="{{ config('contact.form_url') }}" target="_blank" rel="noopener noreferrer">
                        お問い合わせフォームを開く
                    </a>
                @endif
                @if (filled(config('contact.email')))
                    <p class="mt-3 text-sm leading-7 text-slate-700">送信先: {{ config('contact.email') }}</p>
                @endif
            </section>

            <section class="mt-8">
                <h2 class="text-xl font-semibold text-slate-900">掲載情報の修正・通報</h2>
                <p class="mt-3 text-sm leading-7 text-slate-700">誤った駐輪場情報、権利侵害のおそれがある画像や投稿内容は、対象の駐輪場詳細ページにある通報機能からご連絡ください。内容を確認し、必要に応じて掲載停止または削除の対応を行います。</p>
            </section>
        </article>
    </div>
</x-app-layout>
