@php
    $reqSelf  = auth()->id() === $emp->id && $emp->role === 'employee';
    $reqAdmin = auth()->user()->isAdmin() && $emp->role === 'employee';
    $myReqs = collect();
    try {
        if (($reqSelf || $reqAdmin) && \Illuminate\Support\Facades\Schema::hasTable('staff_requests')) {
            $myReqs = \App\Models\StaffRequest::where('user_id', $emp->id)->whereIn('type', ['leave', 'late', 'early'])
                ->orderByDesc('created_at')->limit(10)->get();
        }
    } catch (\Throwable $e) {}
@endphp

@if($reqSelf || ($reqAdmin && $myReqs->isNotEmpty()))
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6" id="requests">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:12px;">
        <h3 style="font-size:1.1rem; font-weight:800; color:#0a2540; margin:0;"><x-icon name="send"/> طلبات الإجازة والإذن</h3>
        @if($reqAdmin)
            <a href="/admin/requests" style="font-size:0.85rem; color:#2563eb;">كل الطلبات ←</a>
        @endif
    </div>

    @if(session('req_success'))
        <div style="background:#f0fdf4; border:1px solid #86efac; color:#15803d; padding:8px 12px; border-radius:8px; margin-bottom:10px;">{{ session('req_success') }}</div>
    @endif
    @php $reqErr = $errors->req->first() ?: collect(['type', 'date_from', 'date_to', 'time', 'reason'])->map(fn ($k) => $errors->first($k))->filter()->first(); @endphp
    @if($reqSelf && $reqErr)
        <div style="background:#fef2f2; border:1px solid #fca5a5; color:#b91c1c; padding:8px 12px; border-radius:8px; margin-bottom:10px;">
            {{ $reqErr }}
        </div>
    @endif

    @if($reqSelf)
    <form method="POST" action="/requests" id="reqForm" style="display:grid; gap:10px; margin-bottom:14px;">
        @csrf
        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:6px;">
            @foreach(['leave' => 'إجازة', 'late' => 'إذن تأخير', 'early' => 'خروج مبكر'] as $k => $label)
                <label style="border:2px solid #e5e7eb; border-radius:10px; padding:10px 4px; text-align:center; cursor:pointer; font-size:0.85rem; font-weight:700;" class="req-type">
                    <input type="radio" name="type" value="{{ $k }}" {{ old('type', 'leave') === $k ? 'checked' : '' }} style="display:none;">{{ $label }}
                </label>
            @endforeach
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <label style="flex:1; min-width:130px; font-size:0.8rem; color:#475569;"><span id="lblFrom">من تاريخ</span>
                <input type="date" name="date_from" required value="{{ old('date_from', now()->toDateString()) }}"
                       style="width:100%; padding:9px; border:1px solid #e5e7eb; border-radius:8px; box-sizing:border-box;"></label>
            <label style="flex:1; min-width:130px; font-size:0.8rem; color:#475569;" id="boxTo">إلى تاريخ
                <input type="date" name="date_to" value="{{ old('date_to') }}"
                       style="width:100%; padding:9px; border:1px solid #e5e7eb; border-radius:8px; box-sizing:border-box;"></label>
            <label style="flex:1; min-width:110px; font-size:0.8rem; color:#475569; display:none;" id="boxTime"><span id="lblTime">الساعة</span>
                <input type="time" name="time" value="{{ old('time') }}"
                       style="width:100%; padding:9px; border:1px solid #e5e7eb; border-radius:8px; box-sizing:border-box;"></label>
        </div>
        <textarea name="reason" required maxlength="500" rows="2" placeholder="السبب"
                  style="width:100%; padding:9px; border:1px solid #e5e7eb; border-radius:8px; box-sizing:border-box; font-family:inherit;">{{ old('reason') }}</textarea>
        <button type="submit" style="background:#2563eb; color:#fff; border:none; padding:12px; border-radius:10px; font-weight:800; cursor:pointer; font-family:inherit;">إرسال الطلب للإدارة</button>
    </form>
    <script>
    (function () {
        const f = document.getElementById('reqForm');
        function sync() {
            const t = f.querySelector('input[name=type]:checked').value;
            f.querySelectorAll('.req-type').forEach(l => {
                const on = l.querySelector('input').checked;
                l.style.borderColor = on ? '#2563eb' : '#e5e7eb';
                l.style.background  = on ? '#eff6ff' : '#fff';
            });
            document.getElementById('boxTo').style.display   = t === 'leave' ? '' : 'none';
            document.getElementById('boxTime').style.display = t === 'leave' ? 'none' : '';
            document.getElementById('lblFrom').innerText     = t === 'leave' ? 'من تاريخ' : 'التاريخ';
            document.getElementById('lblTime').innerText     = t === 'late' ? 'أحضر الساعة' : 'أخرج الساعة';
        }
        f.querySelectorAll('input[name=type]').forEach(r => r.addEventListener('change', sync));
        sync();
    })();
    </script>
    @endif

    @foreach($myReqs as $r)
        @php $sc = ['pending' => '#d97706', 'approved' => '#15803d', 'rejected' => '#b91c1c'][$r->status] ?? '#334155'; @endphp
        <div style="border-top:1px solid #f1f5f9; padding:9px 0; display:flex; justify-content:space-between; gap:8px; align-items:center; flex-wrap:wrap;">
            <div style="font-size:0.88rem;">
                <b>{{ $r->typeLabel() }}</b> — {{ $r->summary() }}
                @if($r->admin_note)<div style="font-size:0.78rem; color:#64748b;">رد الإدارة: {{ $r->admin_note }}</div>@endif
            </div>
            <div style="display:flex; gap:6px; align-items:center;">
                <span style="color:{{ $sc }}; font-size:0.8rem; font-weight:800;">{{ $r->statusLabel() }}</span>
                @if($reqSelf && $r->status === 'pending')
                    <form method="POST" action="/requests/{{ $r->id }}/cancel" onsubmit="return confirm('إلغاء الطلب؟')">@csrf
                        <button style="background:none; border:none; color:#94a3b8; font-size:0.78rem; cursor:pointer; text-decoration:underline;">إلغاء</button>
                    </form>
                @endif
            </div>
        </div>
    @endforeach
</div>
@endif
