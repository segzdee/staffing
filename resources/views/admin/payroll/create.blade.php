@extends('admin.layout')

@section('title', 'Create Payroll Run')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Create Payroll Run
            <small>FIN-005: Batch Payment Processing</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.dashboard') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="{{ route('admin.payroll.index') }}">Payroll</a></li>
            <li class="active">Create</li>
        </ol>
    </section>

    <section class="content">
        <div class="max-w-2xl">
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">New Payroll Run</h3>
                    <p class="mt-1 text-sm text-gray-500">Create a new payroll run for processing worker payments.</p>
                </div>

                <form method="POST" action="{{ route('admin.payroll.store') }}" class="p-6">
                    @csrf

                    @if($errors->any())
                    <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Please correct the following errors:</h3>
                                <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                    @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
                        <div class="flex">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    Current pay cycle: <strong>{{ ucfirst($payCycle) }}</strong>.
                                    Dates below are suggested based on the default pay cycle.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="period_start" class="block text-sm font-medium text-gray-700">Period Start</label>
                                <input type="date" name="period_start" id="period_start"
                                    value="{{ old('period_start', $periodStart->format('Y-m-d')) }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <p class="mt-1 text-xs text-gray-500">First day of the pay period</p>
                            </div>

                            <div>
                                <label for="period_end" class="block text-sm font-medium text-gray-700">Period End</label>
                                <input type="date" name="period_end" id="period_end"
                                    value="{{ old('period_end', $periodEnd->format('Y-m-d')) }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <p class="mt-1 text-xs text-gray-500">Last day of the pay period</p>
                            </div>
                        </div>

                        <div>
                            <label for="pay_date" class="block text-sm font-medium text-gray-700">Pay Date</label>
                            <input type="date" name="pay_date" id="pay_date"
                                value="{{ old('pay_date', $suggestedPayDate->format('Y-m-d')) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <p class="mt-1 text-xs text-gray-500">When payments will be disbursed to workers</p>
                        </div>

                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                            <textarea name="notes" id="notes" rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Any notes about this payroll run...">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <a href="{{ route('admin.payroll.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                            Create & Generate Items
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>
@endsection
