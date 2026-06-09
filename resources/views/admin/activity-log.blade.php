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

    <div class="bg-white p-4 rounded shadow overflow-x-auto">
        <table class="w-full text-start border-collapse">
            <thead>
                <tr class="bg-gray-200 text-gray-700">
                    <th class="p-3 text-start">#</th>
                    <th class="p-3 text-start">{{ __('User') }}</th>
                    <th class="p-3 text-start">{{ __('Action') }}</th>
                    <th class="p-3 text-start">{{ __('Target') }}</th>
                    <th class="p-3 text-start">{{ __('Details') }}</th>
                    <th class="p-3 text-start">{{ __('Date') }}</th>
                    <th class="p-3 text-start">IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr class="border-t hover:bg-gray-50">
                    <td class="p-3 text-gray-500 text-sm">{{ $log->id }}</td>
                    <td class="p-3 font-medium">{{ $log->user?->name ?? '—' }}</td>
                    <td class="p-3">
                        <span style="background:#dbeafe; color:#1d4ed8; padding:2px 8px; border-radius:12px; font-size:0.8rem; font-weight:600;">
                            {{ $log->action }}
                        </span>
                    </td>
                    <td class="p-3">{{ $log->target ?? '—' }}</td>
                    <td class="p-3 text-sm text-gray-600">{{ $log->details ?? '—' }}</td>
                    <td class="p-3 text-sm text-gray-500">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                    <td class="p-3 text-sm text-gray-400">{{ $log->ip_address }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="p-6 text-center text-gray-400">{{ __('No activity recorded yet.') }}</td>
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
