<x-app-layout
    title="利用規約"
    description="BikeParkingの利用規約です。"
    :canonical="route('terms')">
    <div class="bp-shell max-w-3xl">
        <article class="bp-panel p-6 sm:p-8">
            <h1 class="text-3xl font-bold text-slate-900">利用規約</h1>
            <p class="mt-4 text-sm leading-7 text-slate-700">この利用規約は、BikeParking運営（以下「運営者」）が提供するBikeParking（以下「本サービス」）の利用条件を定めるものです。</p>

            <section class="mt-8">
                <h2 class="text-xl font-semibold text-slate-900">サービスと投稿内容</h2>
                <p class="mt-3 text-sm leading-7 text-slate-700">本サービスは、利用者が駐輪場情報を登録、検索、閲覧および共同で編集できるサービスです。投稿した情報は他の利用者に公開されます。運営者は正確性・最新性・利用可能性を保証せず、利用者は実際の施設状況を確認したうえで利用してください。</p>
            </section>

            <section class="mt-8">
                <h2 class="text-xl font-semibold text-slate-900">共同編集の責任</h2>
                <p class="mt-3 text-sm leading-7 text-slate-700">ログインした利用者は、登録者かどうかにかかわらず駐輪場情報を編集できます。編集者は、根拠のある正確な情報を入力し、第三者の権利やプライバシーを侵害しない責任を負います。運営者は投稿内容を事前に確認する義務を負わず、利用者間または利用者と第三者の間で生じた損害について責任を負いません。</p>
            </section>

            <section class="mt-8">
                <h2 class="text-xl font-semibold text-slate-900">禁止行為</h2>
                <ul class="mt-3 list-disc space-y-2 pl-5 text-sm leading-6 text-slate-700">
                    <li>虚偽、不正確、誤解を招く情報、または他者の個人情報を投稿する行為</li>
                    <li>著作権、肖像権、プライバシーその他の第三者の権利を侵害する行為</li>
                    <li>法令、公序良俗に反する行為、サービスの運営を妨害する行為、不正アクセスまたはその試み</li>
                    <li>営利目的の宣伝、スパム、なりすまし、その他本サービスの目的に反する行為</li>
                </ul>
            </section>

            <section class="mt-8">
                <h2 class="text-xl font-semibold text-slate-900">掲載停止・削除</h2>
                <p class="mt-3 text-sm leading-7 text-slate-700">運営者は、禁止行為、誤登録、権利侵害のおそれ、法令上の要請その他必要と判断した場合、事前の通知なく投稿内容の修正、非表示、削除またはアカウントの利用停止を行うことがあります。掲載内容に問題がある場合は、各駐輪場ページの通報機能からお知らせください。誤登録の削除は管理者が内容と理由を確認したうえで対応します。</p>
            </section>

            <section class="mt-8">
                <h2 class="text-xl font-semibold text-slate-900">規約の変更</h2>
                <p class="mt-3 text-sm leading-7 text-slate-700">運営者は必要に応じて本規約を変更できます。変更後の規約は本ページに掲載した時点から効力を生じます。</p>
            </section>
        </article>
    </div>
</x-app-layout>
