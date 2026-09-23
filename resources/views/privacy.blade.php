<x-app-layout
    title="プライバシーポリシー"
    description="motolotzのプライバシーポリシーです。"
    :canonical="route('privacy')">
    <div class="bp-shell max-w-3xl">
        <article class="bp-panel p-6 sm:p-8">
            <h1 class="text-3xl font-bold text-slate-900">プライバシーポリシー</h1>
            <p class="mt-4 text-sm leading-7 text-slate-700">
                motolotz運営（以下「運営者」）は、利用者の情報を適切に取り扱うため、本ポリシーを定めます。
            </p>

            <section class="mt-8">
                <h2 class="text-xl font-semibold text-slate-900">収集する情報</h2>
                <ul class="mt-3 list-disc space-y-2 pl-5 text-sm leading-6 text-slate-700">
                    <li>アカウント登録・利用時に、ユーザーID、表示名、都道府県、暗号化されたパスワードを取り扱います。</li>
                    <li>駐輪場、料金、画像、レビュー、通報、削除申請、更新履歴など、利用者が投稿・送信した内容を取り扱います。</li>
                    <li>Cookie、IPアドレス、ブラウザ情報など、サービス提供・不正利用対策・広告配信に必要な技術情報を取り扱うことがあります。</li>
                </ul>
            </section>

            <section class="mt-8">
                <h2 class="text-xl font-semibold text-slate-900">利用目的と共有</h2>
                <ul class="mt-3 list-disc space-y-2 pl-5 text-sm leading-6 text-slate-700">
                    <li>アカウントの認証、駐輪場情報の掲載・検索、投稿内容の表示、問い合わせへの対応、サービス改善および不正利用の防止に利用します。</li>
                    <li>投稿した駐輪場情報、料金、画像、レビューおよび更新履歴は、サービス上で公開され、他の利用者が閲覧できます。</li>
                    <li>法令に基づく場合を除き、利用者を特定できる情報を本人の同意なく第三者へ提供しません。</li>
                </ul>
            </section>

            <section class="mt-8">
                <h2 class="text-xl font-semibold text-slate-900">保存期間と退会後の扱い</h2>
                <p class="mt-3 text-sm leading-7 text-slate-700">
                    アカウント情報は、アカウントを利用している間保存します。退会時は、認証に必要な情報を削除し、同じユーザーIDの再登録を防ぐためユーザーIDそのものを保存しない退会記録を残します。
                </p>
                <p class="mt-3 text-sm leading-7 text-slate-700">
                    駐輪場情報の継続性を保つため、登録した駐輪場・料金・画像、投稿したレビュー、更新履歴は残し、投稿者を特定できない状態に匿名化します。退会した利用者のお気に入りは削除します。法令上または運営上必要な場合を除き、情報を保持する必要がなくなったときは削除または匿名化します。
                </p>
            </section>

            <section class="mt-8">
                <h2 class="text-xl font-semibold text-slate-900">広告とCookie</h2>
                <p class="mt-3 text-sm leading-7 text-slate-700">
                    Google AdSense による広告を表示する場合、Google を含む第三者配信事業者が、Cookie、ウェブビーコン、IP アドレスなどを利用して広告を配信・測定することがあります。これにより、利用者の当サイトや他サイトへの過去のアクセスに基づいた広告が表示される場合があります。
                </p>
                <p class="mt-3 text-sm leading-7 text-slate-700">
                    パーソナライズド広告は Google の広告設定から管理できます。Google によるデータ利用の詳細は、下記のページをご確認ください。
                </p>
                <div class="mt-3 flex flex-col gap-2 text-sm font-semibold sm:flex-row sm:gap-5">
                    <a class="text-emerald-700 hover:text-emerald-800" href="https://myadcenter.google.com/" target="_blank" rel="noopener noreferrer">
                        Google の広告設定
                    </a>
                    <a class="text-emerald-700 hover:text-emerald-800" href="https://policies.google.com/technologies/partner-sites" target="_blank" rel="noopener noreferrer">
                        Google がパートナーサイトでデータを使用する方法
                    </a>
                </div>
            </section>

            <section class="mt-8">
                <h2 class="text-xl font-semibold text-slate-900">お問い合わせ</h2>
                <p class="mt-3 text-sm leading-7 text-slate-700">
                    本ポリシーや情報の取り扱いに関するお問い合わせは、<a class="font-semibold text-emerald-700 hover:text-emerald-800" href="{{ route('contact') }}">お問い合わせ窓口</a>からご連絡ください。
                </p>
            </section>
        </article>
    </div>
</x-app-layout>
