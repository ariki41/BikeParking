<x-app-layout>
    <div class="bp-shell">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-slate-900">通報管理</h1>
            <p class="mt-2 text-sm text-slate-600">通報内容を確認し、非公開化または指定時点への差し戻しを実行できます。</p>
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
                                    <x-danger-button>非公開にする</x-danger-button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.parking_spots.publish', $report->parkingSpot) }}">
                                    @csrf
                                    <x-primary-button>公開する</x-primary-button>
                                </form>
                            @endif
                            @if ($report->updateHistory)
                                <form method="POST" action="{{ route('admin.parking_spots.histories.restore', [$report->parkingSpot, $report->updateHistory]) }}">
                                    @csrf
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
    </div>
</x-app-layout>
