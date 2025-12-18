@extends('admin.layout')

@section('title', 'Payroll Run: ' . $payrollRun->reference)

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Payroll Run: {{ $payrollRun->reference }}
            <small>{{ $payrollRun->period_start->format('M d') }} - {{ $payrollRun->period_end->format('M d, Y') }}</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.dashboard') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="{{ route('admin.payroll.index') }}">Payroll</a></li>
            <li class="active">{{ $payrollRun->reference }}</li>
        </ol>
    </section>

    <section class="content">
        @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 rounded-md p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <p class="ml-3 text-sm text-green-700">{{ session('success') }}</p>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <p class="ml-3 text-sm text-red-700">{{ session('error') }}</p>
            </div>
        </div>
        @endif

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-500">Status</p>
                @if($payrollRun->status == 'draft')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 mt-2">Draft</span>
                @elseif($payrollRun->status == 'pending_approval')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 mt-2">Pending Approval</span>
                @elseif($payrollRun->status == 'approved')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 mt-2">Approved</span>
                @elseif($payrollRun->status == 'processing')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800 mt-2">Processing</span>
                @elseif($payrollRun->status == 'completed')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 mt-2">Completed</span>
                @elseif($payrollRun->status == 'failed')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 mt-2">Failed</span>
                @endif
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-500">Workers</p>
                <p class="text-2xl font-semibold text-gray-900 mt-2">{{ $summary['totals']['workers'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-500">Gross Amount</p>
                <p class="text-2xl font-semibold text-gray-900 mt-2">${{ number_format($summary['totals']['gross'], 2) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-500">Net Payout</p>
                <p class="text-2xl font-semibold text-green-600 mt-2">${{ number_format($summary['totals']['net'], 2) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Details Panel -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Details</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <p class="text-sm text-gray-500">Reference</p>
                            <p class="font-medium">{{ $payrollRun->reference }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Period</p>
                            <p class="font-medium">{{ $payrollRun->period_start->format('M d, Y') }} - {{ $payrollRun->period_end->format('M d, Y') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Pay Date</p>
                            <p class="font-medium">{{ $payrollRun->pay_date->format('M d, Y') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Created By</p>
                            <p class="font-medium">{{ $payrollRun->creator->name ?? 'N/A' }}</p>
                        </div>
                        @if($payrollRun->approver)
                        <div>
                            <p class="text-sm text-gray-500">Approved By</p>
                            <p class="font-medium">{{ $payrollRun->approver->name }} on {{ $payrollRun->approved_at->format('M d, Y H:i') }}</p>
                        </div>
                        @endif
                        @if($payrollRun->processed_at)
                        <div>
                            <p class="text-sm text-gray-500">Processed At</p>
                            <p class="font-medium">{{ $payrollRun->processed_at->format('M d, Y H:i') }}</p>
                        </div>
                        @endif
                        @if($payrollRun->notes)
                        <div>
                            <p class="text-sm text-gray-500">Notes</p>
                            <p class="font-medium text-sm">{!! nl2br(e($payrollRun->notes)) !!}</p>
                        </div>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="px-6 py-4 border-t border-gray-200 space-y-3">
                        @if($payrollRun->isDraft())
                            <form method="POST" action="{{ route('admin.payroll.regenerate-items', $payrollRun) }}">
                                @csrf
                                <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                    Regenerate Items
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.payroll.submit-for-approval', $payrollRun) }}">
                                @csrf
                                <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700">
                                    Submit for Approval
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.payroll.destroy', $payrollRun) }}" onsubmit="return confirm('Are you sure you want to delete this payroll run?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                                    Delete
                                </button>
                            </form>
                        @endif

                        @if($payrollRun->isPendingApproval())
                            <form method="POST" action="{{ route('admin.payroll.approve', $payrollRun) }}">
                                @csrf
                                <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                                    Approve Payroll
                                </button>
                            </form>
                            <button type="button" onclick="document.getElementById('rejectModal').classList.remove('hidden')" class="w-full inline-flex justify-center items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                                Reject
                            </button>
                        @endif

                        @if($payrollRun->isApproved())
                            <a href="{{ route('admin.payroll.process', $payrollRun) }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                Process Payments
                            </a>
                        @endif

                        @if($payrollRun->isCompleted())
                            <a href="{{ route('admin.payroll.export', ['payrollRun' => $payrollRun, 'format' => 'csv']) }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                Export CSV
                            </a>
                            <a href="{{ route('admin.payroll.export', ['payrollRun' => $payrollRun, 'format' => 'json']) }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                Export JSON
                            </a>
                        @endif

                        @if($payrollRun->isFailed())
                            <form method="POST" action="{{ route('admin.payroll.retry-failed', $payrollRun) }}">
                                @csrf
                                <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700">
                                    Retry Failed Payments
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- Summary by Type -->
                <div class="bg-white rounded-lg shadow mt-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Breakdown by Type</h3>
                    </div>
                    <div class="p-6">
                        <dl class="space-y-3">
                            @foreach($summary['by_type'] as $type => $data)
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500 capitalize">{{ str_replace('_', ' ', $type) }}</dt>
                                <dd class="text-sm font-medium text-gray-900">${{ number_format($data['gross_amount'], 2) }}</dd>
                            </div>
                            @endforeach
                        </dl>
                        <div class="mt-4 pt-4 border-t border-gray-200 flex justify-between">
                            <dt class="text-sm font-medium text-gray-700">Total Deductions</dt>
                            <dd class="text-sm font-medium text-red-600">-${{ number_format($summary['totals']['deductions'], 2) }}</dd>
                        </div>
                        <div class="flex justify-between mt-2">
                            <dt class="text-sm font-medium text-gray-700">Total Taxes</dt>
                            <dd class="text-sm font-medium text-red-600">-${{ number_format($summary['totals']['taxes'], 2) }}</dd>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-200 flex justify-between">
                            <dt class="text-base font-semibold text-gray-900">Net Payout</dt>
                            <dd class="text-base font-semibold text-green-600">${{ number_format($summary['totals']['net'], 2) }}</dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Workers List -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Workers ({{ count($itemsByWorker) }})</h3>
                        @if($payrollRun->canEdit())
                        <button type="button" onclick="document.getElementById('addItemModal').classList.remove('hidden')" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                            Add Manual Item
                        </button>
                        @endif
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Worker</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Gross</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Deductions</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Net</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($itemsByWorker as $workerId => $workerData)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 flex-shrink-0">
                                                <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center text-gray-600 font-medium">
                                                    {{ strtoupper(substr($workerData['worker']->name ?? 'U', 0, 1)) }}
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ $workerData['worker']->name ?? 'Unknown' }}</div>
                                                <div class="text-sm text-gray-500">{{ $workerData['worker']->email ?? '' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $workerData['items']->count() }} items
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        ${{ number_format($workerData['total_gross'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 text-right">
                                        -${{ number_format($workerData['total_deductions'] + $workerData['total_tax'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600 text-right">
                                        ${{ number_format($workerData['total_net'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('admin.payroll.paystub', ['payrollRun' => $payrollRun, 'worker' => $workerId]) }}" class="text-indigo-600 hover:text-indigo-900">
                                            View Paystub
                                        </a>
                                    </td>
                                </tr>
                                <!-- Expanded items row -->
                                <tr class="bg-gray-50">
                                    <td colspan="6" class="px-6 py-2">
                                        <div class="text-xs text-gray-500">
                                            @foreach($workerData['items'] as $item)
                                            <div class="flex justify-between py-1 border-b border-gray-100 last:border-0">
                                                <span class="flex items-center gap-2">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                        @if($item->type == 'regular') bg-blue-100 text-blue-800
                                                        @elseif($item->type == 'overtime') bg-purple-100 text-purple-800
                                                        @elseif($item->type == 'bonus') bg-green-100 text-green-800
                                                        @elseif($item->type == 'adjustment') bg-yellow-100 text-yellow-800
                                                        @else bg-gray-100 text-gray-800 @endif">
                                                        {{ ucfirst($item->type) }}
                                                    </span>
                                                    {{ \Illuminate\Support\Str::limit($item->description, 50) }}
                                                </span>
                                                <span class="flex items-center gap-4">
                                                    <span>{{ $item->hours }}h @ ${{ number_format($item->rate, 2) }}/hr</span>
                                                    <span class="font-medium">${{ number_format($item->net_amount, 2) }}</span>
                                                    @if($payrollRun->canEdit())
                                                    <form method="POST" action="{{ route('admin.payroll.remove-item', ['payrollRun' => $payrollRun, 'item' => $item]) }}" class="inline" onsubmit="return confirm('Remove this item?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                    @endif
                                                </span>
                                            </div>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50" onclick="document.getElementById('rejectModal').classList.add('hidden')"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full">
            <form method="POST" action="{{ route('admin.payroll.reject', $payrollRun) }}">
                @csrf
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Reject Payroll</h3>
                </div>
                <div class="p-6">
                    <label for="reason" class="block text-sm font-medium text-gray-700">Reason for Rejection</label>
                    <textarea name="reason" id="reason" rows="4" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Please explain why this payroll is being rejected..."></textarea>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                        Reject Payroll
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div id="addItemModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50" onclick="document.getElementById('addItemModal').classList.add('hidden')"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full">
            <form method="POST" action="{{ route('admin.payroll.add-item', $payrollRun) }}">
                @csrf
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Add Manual Item</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label for="worker_id" class="block text-sm font-medium text-gray-700">Worker</label>
                        <select name="worker_id" id="worker_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select Worker</option>
                            @foreach(\App\Models\User::where('user_type', 'worker')->orderBy('name')->get() as $worker)
                            <option value="{{ $worker->id }}">{{ $worker->name }} ({{ $worker->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                        <select name="type" id="type" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="bonus">Bonus</option>
                            <option value="adjustment">Adjustment</option>
                            <option value="reimbursement">Reimbursement</option>
                        </select>
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <input type="text" name="description" id="description" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="e.g., Performance bonus Q4">
                    </div>
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700">Amount</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">$</span>
                            </div>
                            <input type="number" name="amount" id="amount" step="0.01" min="0.01" required
                                class="pl-7 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="0.00">
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('addItemModal').classList.add('hidden')" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                        Add Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
