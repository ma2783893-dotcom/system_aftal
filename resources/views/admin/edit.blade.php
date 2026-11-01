@extends('layouts.app')
@section('title', __('Edit Employee'))

@section('content')
<div class="max-w-2xl mx-auto px-4">
    <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100">
        <h1 class="text-2xl font-bold mb-6 text-gray-800">✏️ {{ __('Edit Employee') }}</h1>

        @if($errors->any())
            <div style="background:#fef2f2; border:1px solid #fca5a5; color:#b91c1c; padding:10px 12px; border-radius:8px; margin-bottom:16px; font-size:0.9rem;">
                @foreach($errors->all() as $e)<div>⚠️ {{ $e }}</div>@endforeach
            </div>
        @endif

        <form action="/update-employee/{{ $emp->id }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- Profile Photo Preview --}}
            <div class="mb-6 text-center">
                <img id="photoPreview"
                     src="{{ $emp->photoUrl() }}"
                     style="width:90px; height:90px; border-radius:50%; object-fit:cover; border:3px solid #e5e7eb; margin:0 auto;"
                     onerror="this.src='/assets/default-avatar.svg'">
                <div style="margin-top:0.75rem;">
                    <label style="display:block; font-size:0.875rem; font-weight:600; color:#374151; margin-bottom:0.4rem;">
                        📷 {{ __('Change Profile Photo') }}
                    </label>
                    <input type="file" name="profile_photo" accept="image/*"
                        class="text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer"
                        onchange="previewPhoto(this)">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">{{ __('Name') }}</label>
                <input type="text" name="name" value="{{ $emp->name }}"
                    class="border border-gray-300 p-2 rounded w-full focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">{{ __('Email') }}</label>
                <input type="email" name="email" value="{{ $emp->email }}"
                    class="border border-gray-300 p-2 rounded w-full focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">{{ __('Specialization') }}</label>
                <input type="text" name="specialization" value="{{ $emp->specialization }}"
                    class="border border-gray-300 p-2 rounded w-full focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">{{ __('Current Resume') }}</label>
                @if($emp->hasCvFile())
                    <a href="{{ route('resume.view', $emp->id) }}" target="_blank"
                        class="text-blue-500 underline text-sm">📄 عرض السيرة الذاتية</a>
                @elseif($emp->cv)
                    <span style="color:#b45309; font-size:0.875rem;">⚠️ الملف مفقود من السيرفر، ارفعه من جديد بالأسفل</span>
                @else
                    <span class="text-gray-500 text-sm">{{ __('Not Available') }}</span>
                @endif
            </div>
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-1">تغيير السيرة الذاتية (PDF أو Word أو صورة)</label>
                <input type="file" name="cv" accept=".pdf,.doc,.docx,image/*"
                    class="text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-2 rounded transition">
                    {{ __('Save Changes') }}
                </button>
                <a href="/" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold px-6 py-2 rounded transition text-sm flex items-center">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('photoPreview').src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection
