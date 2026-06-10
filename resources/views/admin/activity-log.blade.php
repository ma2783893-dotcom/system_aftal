@extends('layouts.app')
@section('title', __('Activity Log'))

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">📋 {{ __('Activity Log') }}</h1>
        <a href="/" style="background:#0a2540; color:white; padding:8px 16px; border-radius:8px; text-decoration:none; font-size:0.875rem;">
            ← {{ __('Back to Dashboard') }}
        </a>
    </div>

    {{-- Session Summary --}}
    @if(isset($sessionSummary) && $sessionSummary->count())
    <div class="bg-white p-5 rounded-xl shadow mb-6">
        <h2 style="font-size:1.1rem; font-weight:700; color:#0a2540; margin-bottom:1rem;">📊 ملخص النشاط حسب المستخدم واليوم</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr style="background:#f1f5f9; color:#475569;">
                        <th class="p-3 text-start">المستخدم</th>
                        <th class="p-3 text-start">الدور</th>
                        <th class="p-3 text-start">التاريخ</th>
                        <th class="p-3 text-center">إجمالي الإجراءات</th>
                        <th class="p-3 text-center">إضافة</th>
                        <th class="p-3 text-center">تعديل</th>
                        <th class="p-3 text-center">حذف</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sessionSummary as $s)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="p-3 font-medium">{{ $s->user?->name ?? '—' }}</td>
                        <td class="p-3">
                            @php $role = $s->user?->role ?? '' @endphp
                            <span style="padding:2px 10px; border-radius:20px; font-size:0.75rem; font-weight:600;
                                background:{{ $role === 'admin' ? '#dbeafe' : ($role === 'supervisor' ? '#ede9fe' : '#f3f4f6') }};
                                color:{{ $role === 'admin' ? '#1d4ed8' : ($role === 'supervisor' ? '#6d28d9' : '#6b7280') }};">
                                {{ $role === 'admin' ? 'Admin' : ($role === 'supervisor' ? 'Supervisor' : $role) }}
                            </span>
                        </td>
                        <td class="p-3 text-gray-600">{{ $s->date }}</td>
                        <td class="p-3 text-center">
                            <span style="background:#0a2540; color:white; padding:3px 12px; border-radius:20px; font-weight:700;">
                                {{ $s->total_actions }}
                            </span>
                        </td>
                        <td class="p-3 text-center">
                            <span style="background:#dcfce7; color:#16a34a; padding:2px 10px; border-radius:20px; font-weight:600;">
                                {{ $s->added ?? 0 }}
                            </span>
                        </td>
                        <td class="p-3 text-center">
                            <span style="background:#fef9c3; color:#ca8a04; padding:2px 10px; border-radius:20px; font-weight:600;">
                                {{ $s->edited ?? 0 }}
                            </span>
                        </td>
                        <td class="p-3 text-center">
                            <span style="background:#fee2e2; color:#dc2626; padding:2px 10px; border-radius:20px; font-weight:600;">
                                {{ $s->deleted ?? 0 }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Full Log --}}
    <div class="bg-white p-4 rounded-xl shadow overflow-x-auto">
        <h2 style="font-size:1rem; font-weight:700; color:#0a2540; margin-bottom:1rem;">📄 السجل الكامل</h2>
        <table class="w-full text-start border-collapse">
            <thead>
                <tr style="background:#f1f5f9; color:#475569; font-size:0.85rem;">
                    <th class="p-3 text-start">#</th>
                    <th class="p-3 text-start">{{ __('User') }}</th>
                    <th class="p-3 text-start">الدور</th>
                    <th class="p-3 text-start">{{ __('Action') }}</th>
                    <th class="p-3 text-start">{{ __('Target') }}</th>
                    <th class="p-3 text-start">{{ __('Details') }}</th>
                    <th class="p-3 text-start">{{ __('Date') }}</th>
                    <th class="p-3 text-start">IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr class="border-t hover:bg-gray-50 text-sm">
                    <td class="p-3 text-gray-400">{{ $log->id }}</td>
                    <td class="p-3 font-medium">{{ $log->user?->name ?? '—' }}</td>
                    <td class="p-3">
                        @php $r = $log->user?->role ?? '' @endphp
                        <span style="padding:2px 8px; border-radius:12px; font-size:0.75rem; font-weight:600;
                            background:{{ $r === 'admin' ? '#dbeafe' : ($r === 'supervisor' ? '#ede9fe' : '#f3f4f6') }};
                            color:{{ $r === 'admin' ? '#1d4ed8' : ($r === 'supervisor' ? '#6d28d9' : '#6b7280') }};">
                            {{ $r === 'admin' ? 'Admin' : ($r === 'supervisor' ? 'Supervisor' : ($r ?: '—')) }}
                        </span>
                    </td>
                    <td class="p-3">
                        @php
                            $ac = $log->action ?? '';
                            if (str_contains($ac, 'add'))    { $bg = '#dcfce7'; $fg = '#16a34a'; }
                            elseif (str_contains($ac, 'edit'))   { $bg = '#fef9c3'; $fg = '#ca8a04'; }
                            elseif (str_contains($ac, 'delete')) { $bg = '#fee2e2'; $fg = '#dc2626'; }
                            else                              { $bg = '#f1f5f9'; $fg = '#475569'; }
                        @endphp
                        <span style="background:{{ $bg }}; color:{{ $fg }}; padding:2px 10px; border-radius:12px; font-size:0.8rem; font-weight:600;">
                            {{ $ac }}
                        </span>
                    </td>
                    <td class="p-3">{{ $log->target ?? '—' }}</td>
                    <td class="p-3 text-gray-500" style="max-width:220px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        {{ $log->details ?? '—' }}
                    </td>
                    <td class="p-3 text-gray-500" style="white-space:nowrap;">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                    <td class="p-3 text-gray-400 text-xs">{{ $log->ip_address }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="p-8 text-center text-gray-400">{{ __('No activity recorded yet.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if(method_exists($logs, 'links'))
            <div class="mt-4">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
@endsection
