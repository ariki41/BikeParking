<x-app-layout>
    <div class="bp-shell">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-slate-900">通報・削除管理</h1>
            <p class="mt-2 text-sm text-slate-600">通報内容の確認、掲載停止・差し戻し、誤登録の削除確認を行えます。</p>
        </div>

        @if (session('status'))
            <p class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</p>
        @endif

        <div class="space-y-5">
            @forelse ($reports as $report)
                <article class="bp-panel p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">{{ $report->parkingSpot?->name ?? '削除済みの駐輪場' }}</h2>
                            <p class="mt-1 text-xs text-slate-500">通報者: {{ $report->reporter?->name ?? '退会済みユーザー' }} / {{ $report->created_at->format('Y-m-d H:i') }}</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $report->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600' }}">{{ $report->status === 'pending' ? '未対応' : '対応済み' }}</span>
                    </div>
                    <p class="mt-4 whitespace-pre-wrap text-sm text-slate-700">{{ $report->reason }}</p>
                    @if ($report->updateHistory)
                        <p class="mt-3 text-sm text-slate-600">対象更新: {{ $report->updateHistory->created_at?->format('Y-m-d H:i') }}（{{ $report->updateHistory->change_summary }}）</p>
                    @else
                        <p class="mt-3 text-sm text-slate-600">対象: 駐輪場全体</p>
                    @endif
                    @if ($report->parkingSpot)
                        <div class="mt-5 flex flex-wrap gap-3 border-t border-slate-100 pt-4">
                            @if ($report->parkingSpot->is_published)
                                <form method="POST" action="{{ route('admin.parking_spots.hide', $report->parkingSpot) }}">
                                    @csrf
                                    <label class="sr-only" for="hide-reason-{{ $report->id }}">掲載停止の理由</label>
                                    <textarea class="bp-input mb-2 min-h-20 text-sm" id="hide-reason-{{ $report->id }}" name="moderation_reason" maxlength="2000" required placeholder="掲載停止の理由"></textarea>
                                    <x-danger-button>非公開にする</x-danger-button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.parking_spots.publish', $report->parkingSpot) }}">
                                    @csrf
                                    <label class="sr-only" for="publish-reason-{{ $report->id }}">再公開の理由</label>
                                    <textarea class="bp-input mb-2 min-h-20 text-sm" id="publish-reason-{{ $report->id }}" name="moderation_reason" maxlength="2000" required placeholder="再公開の理由"></textarea>
                                    <x-primary-button>公開する</x-primary-button>
                                </form>
                            @endif
                            @if ($report->updateHistory)
                                <form method="POST" action="{{ route('admin.parking_spots.histories.restore', [$report->parkingSpot, $report->updateHistory]) }}">
                                    @csrf
                                    <label class="sr-only" for="restore-reason-{{ $report->id }}">差し戻しの理由</label>
                                    <textarea class="bp-input mb-2 min-h-20 text-sm" id="restore-reason-{{ $report->id }}" name="moderation_reason" maxlength="2000" required placeholder="差し戻しの理由"></textarea>
                                    <x-secondary-button type="submit">この更新時点へ差し戻す</x-secondary-button>
                                </form>
                            @endif
                            @if ($report->status === 'pending')
                                <form method="POST" action="{{ route('admin.parking_spot_reports.resolve', $report) }}">
                                    @csrf
                                    <x-secondary-button type="submit">対応済みにする</x-secondary-button>
                                </form>
                            @endif
                        </div>
                    @endif
                    @if ($report->reviewed_at)
                        <p class="mt-4 text-xs text-slate-500">対応者: {{ $report->reviewer?->name ?? '退会済みユーザー' }} / {{ $report->reviewed_at->format('Y-m-d H:i') }}</p>
                    @endif
                </article>
            @empty
                <div class="bp-panel p-5 text-sm text-slate-500">通報はありません。</div>
            @endforelse
        </div>
        <div class="mt-6">{{ $reports->links() }}</div>

        <section class="mt-10">
            <h2 class="text-2xl font-bold text-slate-900">誤登録の削除申請</h2>
            <div class="mt-5 space-y-5">
                @forelse ($deletionRequests as $deletionRequest)
                    <article class="bp-panel p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">{{ $deletionRequest->parking_spot_name }}</h3>
                                <p class="mt-1 text-xs text-slate-500">申請者: {{ $deletionRequest->requester?->name ?? '退会済みユーザー' }} / {{ $deletionRequest->created_at->format('Y-m-d H:i') }}</p>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $deletionRequest->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600' }}">{{ $deletionRequest->status === 'pending' ? '確認待ち' : '削除済み' }}</span>
                        </div>
                        <p class="mt-4 whitespace-pre-wrap text-sm text-slate-700">{{ $deletionRequest->reason }}</p>
                        @if ($deletionRequest->status === 'pending' && $deletionRequest->parkingSpot)
                            <form class="mt-5 border-t border-slate-100 pt-4" method="POST" action="{{ route('admin.parking_spot_deletion_requests.delete', $deletionRequest) }}">
                                @csrf
                                <label class="block text-sm font-semibold text-slate-700" for="delete-reason-{{ $deletionRequest->id }}">削除を確定する理由</label>
                                <textarea class="bp-input mt-2 min-h-24 text-sm" id="delete-reason-{{ $deletionRequest->id }}" name="moderation_reason" maxlength="2000" required></textarea>
                                <x-danger-button class="mt-3">確認して物理削除する</x-danger-button>
                            </form>
                        @endif
                        @if ($deletionRequest->reviewed_at)
                            <p class="mt-4 text-xs text-slate-500">実行者: {{ $deletionRequest->reviewer?->name ?? '退会済みユーザー' }} / {{ $deletionRequest->reviewed_at->format('Y-m-d H:i') }}</p>
                            <p class="mt-1 whitespace-pre-wrap text-xs text-slate-500">理由: {{ $deletionRequest->resolution_reason }}</p>
                        @endif
                    </article>
                @empty
                    <div class="bp-panel p-5 text-sm text-slate-500">誤登録の削除申請はありません。</div>
                @endforelse
            </div>
            <div class="mt-6">{{ $deletionRequests->links() }}</div>
        </section>
    </div>
</x-app-layout>
