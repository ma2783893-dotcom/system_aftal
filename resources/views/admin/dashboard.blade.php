@extends('layouts.app')
@section('title', __('Admin Dashboard'))

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">{{ __('Employee List') }}</h1>
        <button onclick="document.getElementById('addModal').style.display='flex'"
            style="background:#16a34a; color:white; padding:10px 20px; border-radius:8px; border:none; cursor:pointer; font-size:1rem; font-weight:bold;">
            + {{ __('Add New Employee') }}
        </button>
    </div>

    <!-- Add Employee Modal -->
    <div id="addModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center;">
        <div style="background:white; padding:2rem; border-radius:1rem; width:90%; max-width:600px; position:relative;">
            <button onclick="document.getElementById('addModal').style.display='none'"
                style="position:absolute; top:10px; left:10px; background:none; border:none; font-size:1.5rem; cursor:pointer; line-height:1;">✕</button>
            <h2 style="margin-bottom:1.5rem; color:#0a2540; font-size:1.25rem; font-weight:700;">{{ __('Add New Employee') }}</h2>
            <form action="/add-employee" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <input type="text" name="name" placeholder="{{ __('Name') }}" class="border border-gray-300 p-2 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                    <input type="email" name="email" placeholder="{{ __('Email') }}" class="border border-gray-300 p-2 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                    <input type="text" name="specialization" placeholder="{{ __('Specialization') }}" class="border border-gray-300 p-2 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                    <input type="password" name="password" placeholder="{{ __('Password') }}" class="border border-gray-300 p-2 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                </div>
                <div class="flex items-center justify-between">
                    <input type="file" name="cv" accept=".pdf" class="text-sm file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold px-6 py-2 rounded transition">
                        {{ __('Add New Employee') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Employee Table -->
    <div class="bg-white p-4 rounded shadow overflow-x-auto">
        <table class="w-full text-start border-collapse">
            <thead>
                <tr class="bg-gray-200 text-gray-700">
                    <th class="p-3 text-start">{{ __('Name') }}</th>
                    <th class="p-3 text-start">{{ __('Email') }}</th>
                    <th class="p-3 text-start">{{ __('Specialization') }}</th>
                    <th class="p-3 text-start">{{ __('Resume') }}</th>
                    <th class="p-3 text-start">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $emp)
                    <tr class="border-t">
                        <td class="p-3">{{ $emp->name }}</td>
                        <td class="p-3">{{ $emp->email }}</td>
                        <td class="p-3">{{ $emp->specialization }}</td>
                        <td class="p-3">
                            @if($emp->cv)
                                <div class="flex gap-2">
                                    <a href="/profile/{{ $emp->id }}"
                                        class="text-blue-500 hover:text-blue-700 font-medium underline">{{ __('View Profile') }}</a>
                                    <span class="text-gray-300">|</span>
                                    <a href="{{ route('resume.download', $emp->id) }}"
                                        class="text-green-500 hover:text-green-700 font-medium underline">{{ __('Download') }}</a>
                                </div>
                            @else
                                <span class="text-gray-500">{{ __('Not Available') }}</span>
                            @endif
                        </td>
                        <td class="p-3 flex gap-2">
                            <a href="/edit-employee/{{ $emp->id }}" class="bg-yellow-500 text-white px-2 py-1 rounded hover:bg-yellow-600 text-sm">{{ __('Edit') }}</a>
                            <a href="/delete-employee/{{ $emp->id }}" class="bg-red-600 text-white px-2 py-1 rounded hover:bg-red-700 text-sm" onclick="return confirm('Are you sure?')">{{ __('Delete') }}</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
