<div class="bg-white border text-start border-gray-200 p-6 rounded-xl shadow-sm mb-8">
    <div class="flex justify-between items-center mb-6 border-b pb-4">
        <h3 class="text-lg font-bold text-gray-800">{{ __('Financial & Hourly Management') }}</h3>
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <label class="text-sm font-semibold text-gray-600 block">{{ __('Semester:') }}</label>
                <select wire:model.live="semesterId"
                    class="border border-gray-300 rounded p-1.5 text-sm focus:ring-1 focus:ring-indigo-500 bg-gray-50 no-print">
                    <option value="">{{ __('Select Semester') }}</option>
                    @foreach($semesters as $semester)
                        <option value="{{ $semester->id }}">{{ $semester->name }} {{ $semester->is_current ? __('(Current)') : '' }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" onclick="window.print()" class="no-print bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-1.5 px-4 rounded-lg shadow-sm transition-all flex items-center gap-2 text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                {{ __('Print Financial Report') }}
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-3 mb-5 rounded">
            {{ session('message') }}
        </div>
    @endif

    @if(auth()->check() && auth()->user()->isAdmin())
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="bg-blue-50/50 p-5 rounded-lg border border-blue-100">
                <h4 class="text-blue-800 font-semibold mb-4 border-b border-blue-200 pb-2">{{ __('Hourly Wage Structure') }}</h4>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">{{ __('Total Hours') }}</label>
                        <input type="number" step="0.5" wire:model.live="hours"
                            class="w-full border border-gray-300 p-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                            placeholder="{{ __('e.g. 20') }}">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">{{ __('Hourly Rate') }}</label>
                        <input type="number" step="0.01" wire:model.live="hourlyRate"
                            class="w-full border border-gray-300 p-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                            placeholder="{{ __('e.g. 40') }}">
                    </div>
                </div>
                <div class="bg-blue-600 text-white p-4 rounded-lg flex justify-between items-center shadow-inner mt-4">
                    <span class="font-medium text-blue-100">{{ __('Total Earned (Hours)') }}</span>
                    <span class="text-2xl font-bold">{{ number_format((float) $totalDue, 2) }}</span>
                </div>
                <p class="text-xs text-blue-400 mt-2 italic">* {{ __('This updates automatically without saving.') }}</p>
            </div>

            <div class="bg-green-50/50 p-5 rounded-lg border border-green-100">
                <h4 class="text-green-800 font-semibold mb-4 border-b border-green-200 pb-2">{{ __('Fixed Salary Structure') }}</h4>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">{{ __('Basic Salary') }}</label>
                        <input type="number" step="0.01" wire:model="salary"
                            class="w-full border border-gray-300 p-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-green-500 bg-white" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">{{ __('Bonus (+)') }}</label>
                            <input type="number" step="0.01" wire:model="bonus"
                                class="w-full border border-gray-300 p-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-green-500 bg-white" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">{{ __('Deductions (-)') }}</label>
                            <input type="number" step="0.01" wire:model="deductions"
                                class="w-full border border-gray-300 p-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-red-500 bg-white" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 pt-4 border-t flex flex-col md:flex-row justify-between items-center bg-gray-50 p-4 rounded-lg border border-gray-200 gap-4">
            <div>
                <span class="text-sm text-gray-500 block mb-1">{{ __('Net Semester Total (Salary + Bonus + Hourly - Deductions)') }}</span>
                <span class="text-xl font-bold text-gray-800">
                    {{ number_format(floatval($salary) + floatval($bonus) + floatval($totalDue) - floatval($deductions), 2) }}
                </span>
            </div>
            <button wire:click="save"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-lg shadow-md transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                </svg>
                {{ __('Save Semester Data') }}
            </button>
        </div>
    @else
        <div class="py-8 text-center rounded-xl border border-dashed border-gray-200 bg-gray-50">
            <svg class="mx-auto w-12 h-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            <p class="text-gray-500 font-medium">{{ __('Financial details are managed by the administration.') }}</p>
            <p class="text-gray-400 text-sm mt-1">{{ __('Please contact your admin for salary information.') }}</p>
        </div>
    @endif

    <!-- Print Layout -->
    <div id="print-section" class="hidden" dir="rtl">

        <!-- Print Header -->
        <div style="text-align:center; margin-bottom:2rem; padding-bottom:1.5rem; border-bottom:3px solid #0a2540;">
            <img src="/assets/logo-dark.jpg"
                 style="width:100px; height:100px; object-fit:contain;
                        border-radius:50%; margin:0 auto 0.75rem auto;
                        display:block; border:3px solid #0a2540;">
            <h1 style="font-size:1.6rem; font-weight:900; color:#0a2540; margin:0 0 0.25rem 0;">
                جامعة الأفضل الدولية
            </h1>
            <p style="font-size:0.9rem; color:#64748b; margin:0 0 0.25rem 0;">
                Al-Afdal International University
            </p>
            <h2 style="font-size:1.1rem; font-weight:700; color:#1e3a8a; margin:0.5rem 0 0 0;">
                {{ __('Financial Report') }}
            </h2>
        </div>

        <!-- Table -->
        <table class="w-full border-collapse border border-gray-800 text-right">
            <tbody>
                <tr>
                    <th class="border border-gray-800 p-3 bg-gray-100" style="width:20%;">{{ __('Name') }}</th>
                    <td class="border border-gray-800 p-3" style="width:30%;">{{ $employee?->name ?? 'N/A' }}</td>
                    <th class="border border-gray-800 p-3 bg-gray-100" style="width:20%;">{{ __('Major') }}</th>
                    <td class="border border-gray-800 p-3" style="width:30%;">{{ $employee?->specialization ?? __('Not Available') }}</td>
                </tr>
                <tr>
                    <th class="border border-gray-800 p-3 bg-gray-100">{{ __('Semester') }}</th>
                    <td class="border border-gray-800 p-3" colspan="3">{{ $selectedSemester?->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th class="border border-gray-800 p-3 bg-gray-100">{{ __('Basic Salary') }}</th>
                    <td class="border border-gray-800 p-3">{{ number_format((float) $salary, 2) }}</td>
                    <th class="border border-gray-800 p-3 bg-gray-100">{{ __('Bonuses') }}</th>
                    <td class="border border-gray-800 p-3">{{ number_format((float) $bonus, 2) }}</td>
                </tr>
                <tr>
                    <th class="border border-gray-800 p-3 bg-gray-100">{{ __('Deductions') }}</th>
                    <td class="border border-gray-800 p-3">{{ number_format((float) $deductions, 2) }}</td>
                    <th class="border border-gray-800 p-3 bg-gray-100">{{ __('Working Hours') }}</th>
                    <td class="border border-gray-800 p-3">{{ $hours }} {{ __('hrs') }}</td>
                </tr>
                <tr>
                    <th class="border border-gray-800 p-4 bg-gray-100 text-lg" colspan="2">{{ __('Total Payable') }}</th>
                    <td class="border border-gray-800 p-4 font-bold text-xl text-center" colspan="2">
                        {{ number_format(floatval($salary) + floatval($bonus) + floatval($totalDue) - floatval($deductions), 2) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Signatures -->
        <div style="margin-top:80px; padding:0 5%; display:flex; justify-content:space-between;" dir="ltr">
            <div style="text-align:center; min-width:160px;">
                <p style="font-weight:bold; font-size:1.1rem; margin-bottom:50px;" dir="rtl">رئيس الجامعة</p>
                <div style="border-top:1px solid #333; width:160px;"></div>
                <p style="font-size:0.8rem; color:#555; margin-top:6px;" dir="rtl">التوقيع</p>
            </div>
            <div style="text-align:center; min-width:160px;">
                <p style="font-weight:bold; font-size:1.1rem; margin-bottom:50px;" dir="rtl">محاسب الجامعة</p>
                <div style="border-top:1px solid #333; width:160px;"></div>
                <p style="font-size:0.8rem; color:#555; margin-top:6px;" dir="rtl">التوقيع</p>
            </div>
        </div>
    </div>

    <style>
    @media print {
        /* Hide everything except print section */
        body > * { display: none !important; }
        #print-section { display: block !important; }

        #print-section {
            display: block !important;
            visibility: visible !important;
            position: static !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 20px !important;
            background: white !important;
            color: black !important;
        }

        #print-section * {
            visibility: visible !important;
            color: black !important;
        }

        .no-print { display: none !important; }

        table { width: 100% !important; border-collapse: collapse !important; }
        th, td { border: 1px solid #333 !important; padding: 8px !important; }

        html, body {
            height: auto !important;
            overflow: visible !important;
            background: white !important;
        }
    }

    @page {
        size: A4;
        margin: 15mm 10mm;
    }
    </style>
</div>