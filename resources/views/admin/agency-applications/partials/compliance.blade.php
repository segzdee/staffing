{{-- Compliance Checklist UI - AGY-REG-003 --}}
<div class="bg-white rounded-lg border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-medium text-gray-900">Compliance Checks</h3>
            <p class="mt-1 text-sm text-gray-500">
                {{ $application->complianceChecks->count() }} {{ Str::plural('check', $application->complianceChecks->count()) }} configured
            </p>
        </div>
        <div class="flex items-center gap-4">
            <!-- Compliance Progress -->
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500">{{ $application->getComplianceCompletionPercentage() }}% complete</span>
                <div class="w-24 bg-gray-200 rounded-full h-2">
                    <div class="bg-purple-600 h-2 rounded-full" style="width: {{ $application->getComplianceCompletionPercentage() }}%"></div>
                </div>
            </div>

            @if(!$application->isTerminal() && $application->isDocumentsVerified())
                @if($application->complianceChecks->count() === 0)
                    <form method="POST" action="{{ route('admin.agency-applications.start-compliance', $application->id) }}" class="inline">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center px-3 py-1.5 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Initialize Checks
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>

    @if(!$application->isDocumentsVerified() && $application->complianceChecks->count() === 0)
        <div class="p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Documents must be verified first</h3>
            <p class="mt-1 text-sm text-gray-500">Complete the document verification before starting compliance checks.</p>
        </div>
    @elseif($application->complianceChecks->count() > 0)
        <div class="divide-y divide-gray-200">
            @foreach($application->complianceChecks as $check)
                <div class="p-6 {{ $check->isFailed() ? 'bg-red-50' : ($check->isPassed() || $check->isOverridden() ? 'bg-green-50' : '') }}">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-4 flex-1">
                            <!-- Check Icon -->
                            <div class="flex-shrink-0">
                                @php
                                    $iconBg = match($check->status) {
                                        'passed', 'overridden' => 'bg-green-100 text-green-600',
                                        'failed' => 'bg-red-100 text-red-600',
                                        'in_progress' => 'bg-blue-100 text-blue-600',
                                        'manual_review' => 'bg-orange-100 text-orange-600',
                                        default => 'bg-gray-100 text-gray-500'
                                    };
                                @endphp
                                <div class="w-10 h-10 rounded-full {{ $iconBg }} flex items-center justify-center">
                                    @if($check->isPassed() || $check->isOverridden())
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @elseif($check->isFailed())
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    @elseif($check->isInProgress())
                                        <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    @endif
                                </div>
                            </div>

                            <!-- Check Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h4 class="text-sm font-medium text-gray-900">
                                        {{ $check->name ?? $check->getTypeLabel() }}
                                    </h4>
                                    @php
                                        $statusBadges = [
                                            'pending' => 'bg-gray-100 text-gray-700',
                                            'in_progress' => 'bg-blue-100 text-blue-700',
                                            'passed' => 'bg-green-100 text-green-700',
                                            'failed' => 'bg-red-100 text-red-700',
                                            'manual_review' => 'bg-orange-100 text-orange-700',
                                            'overridden' => 'bg-purple-100 text-purple-700',
                                            'expired' => 'bg-gray-100 text-gray-700',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusBadges[$check->status] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ $check->getStatusLabel() }}
                                    </span>
                                    @if($check->is_required)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-50 text-red-700">
                                            Required
                                        </span>
                                    @endif
                                    @if($check->risk_level)
                                        @php
                                            $riskColors = [
                                                'low' => 'bg-green-50 text-green-700',
                                                'medium' => 'bg-yellow-50 text-yellow-700',
                                                'high' => 'bg-orange-50 text-orange-700',
                                                'critical' => 'bg-red-50 text-red-700',
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $riskColors[$check->risk_level] ?? 'bg-gray-50 text-gray-700' }}">
                                            {{ ucfirst($check->risk_level) }} Risk
                                        </span>
                                    @endif
                                </div>

                                @if($check->description)
                                    <p class="mt-1 text-sm text-gray-500">{{ $check->description }}</p>
                                @endif

                                @if($check->provider)
                                    <p class="mt-1 text-xs text-gray-400">
                                        Provider: {{ $check->provider }}
                                        @if($check->external_reference)
                                            | Ref: {{ $check->external_reference }}
                                        @endif
                                    </p>
                                @endif

                                @if($check->completed_at)
                                    <p class="mt-1 text-xs text-gray-400">
                                        Completed {{ $check->completed_at->diffForHumans() }}
                                        @if($check->performedBy)
                                            by {{ $check->performedBy->name }}
                                        @endif
                                    </p>
                                @endif

                                @if($check->failure_reason)
                                    <div class="mt-2 p-2 bg-red-100 rounded text-sm text-red-700">
                                        <span class="font-medium">Failure reason:</span> {{ $check->failure_reason }}
                                    </div>
                                @endif

                                @if($check->notes)
                                    <div class="mt-2 p-2 bg-gray-100 rounded text-sm text-gray-700">
                                        <span class="font-medium">Notes:</span> {{ $check->notes }}
                                    </div>
                                @endif

                                @if($check->isOverridden())
                                    <div class="mt-2 p-2 bg-purple-100 rounded text-sm text-purple-700">
                                        <span class="font-medium">Override reason:</span> {{ $check->override_reason }}
                                        @if($check->overriddenBy)
                                            <br><span class="text-xs">Overridden by {{ $check->overriddenBy->name }} on {{ $check->overridden_at->format('M j, Y') }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Check Actions -->
                        @if(!$application->isTerminal())
                            <div class="flex-shrink-0">
                                <div x-data="{ open: false }" class="relative">
                                    <button @click="open = !open" type="button"
                                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                        Update
                                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>

                                    <div x-show="open" @click.away="open = false"
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="transform opacity-0 scale-95"
                                         x-transition:enter-end="transform opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-75"
                                         x-transition:leave-start="transform opacity-100 scale-100"
                                         x-transition:leave-end="transform opacity-0 scale-95"
                                         class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-10"
                                         style="display: none;">
                                        <form method="POST" action="{{ route('admin.agency-applications.review-compliance', $application->id) }}">
                                            @csrf
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="check_id" value="{{ $check->id }}">

                                            <button type="submit" name="status" value="passed"
                                                    class="w-full text-left px-4 py-2 text-sm text-green-700 hover:bg-green-50 flex items-center">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                Mark as Passed
                                            </button>
                                        </form>

                                        <button type="button" onclick="openFailModal({{ $check->id }}, '{{ $check->getTypeLabel() }}')"
                                                class="w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50 flex items-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            Mark as Failed
                                        </button>

                                        <form method="POST" action="{{ route('admin.agency-applications.review-compliance', $application->id) }}">
                                            @csrf
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="check_id" value="{{ $check->id }}">

                                            <button type="submit" name="status" value="in_progress"
                                                    class="w-full text-left px-4 py-2 text-sm text-blue-700 hover:bg-blue-50 flex items-center">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                                Mark In Progress
                                            </button>

                                            <button type="submit" name="status" value="manual_review"
                                                    class="w-full text-left px-4 py-2 text-sm text-orange-700 hover:bg-orange-50 flex items-center">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                Needs Manual Review
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Missing Checks Warning -->
        @if(count($missingChecks) > 0 && !$application->isTerminal())
            <div class="px-6 py-4 bg-yellow-50 border-t border-yellow-200">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-yellow-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <h4 class="text-sm font-medium text-yellow-800">Missing Required Checks</h4>
                        <p class="mt-1 text-sm text-yellow-700">
                            The following required checks have not been added:
                        </p>
                        <ul class="mt-2 space-y-1">
                            @foreach($missingChecks as $checkType)
                                <li class="text-sm text-yellow-700">
                                    - {{ \App\Models\AgencyComplianceCheck::getCheckTypeOptions()[$checkType] ?? $checkType }}
                                </li>
                            @endforeach
                        </ul>
                        <form method="POST" action="{{ route('admin.agency-applications.start-compliance', $application->id) }}" class="mt-3">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700 transition-colors">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Add Missing Checks
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- Run All Checks Button -->
        @if(!$application->isTerminal() && $application->complianceChecks->where('status', 'pending')->count() > 0)
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                <form method="POST" action="{{ route('admin.agency-applications.review-compliance', $application->id) }}">
                    @csrf
                    <input type="hidden" name="action" value="run_all">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Run All Pending Checks
                    </button>
                </form>
            </div>
        @endif
    @else
        <div class="p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No compliance checks configured</h3>
            <p class="mt-1 text-sm text-gray-500">
                @if($application->isDocumentsVerified())
                    Click "Initialize Checks" to set up the required compliance checks.
                @else
                    Complete document verification first, then initialize compliance checks.
                @endif
            </p>
        </div>
    @endif
</div>

<!-- Fail Check Modal -->
<div id="failCheckModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeFailModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST" action="{{ route('admin.agency-applications.review-compliance', $application->id) }}" id="failCheckForm">
                @csrf
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="check_id" id="fail_check_id">
                <input type="hidden" name="status" value="failed">

                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Mark Check as Failed
                            </h3>
                            <p class="mt-1 text-sm text-gray-500" id="fail_check_name"></p>
                            <div class="mt-4">
                                <label for="failure_reason" class="block text-sm font-medium text-gray-700">Failure Reason <span class="text-red-500">*</span></label>
                                <textarea name="failure_reason" id="failure_reason" rows="3" required
                                          class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-red-500 focus:border-red-500"
                                          placeholder="Explain why this check failed..."></textarea>
                            </div>
                            <div class="mt-4">
                                <label for="risk_level" class="block text-sm font-medium text-gray-700">Risk Level</label>
                                <select name="risk_level" id="risk_level"
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-red-500 focus:border-red-500">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high" selected>High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                            <div class="mt-4">
                                <label for="fail_notes" class="block text-sm font-medium text-gray-700">Additional Notes</label>
                                <textarea name="notes" id="fail_notes" rows="2"
                                          class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-red-500 focus:border-red-500"
                                          placeholder="Any additional context..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        Mark as Failed
                    </button>
                    <button type="button" onclick="closeFailModal()" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openFailModal(checkId, checkName) {
        document.getElementById('fail_check_id').value = checkId;
        document.getElementById('fail_check_name').textContent = checkName;
        document.getElementById('failCheckModal').classList.remove('hidden');
    }

    function closeFailModal() {
        document.getElementById('failCheckModal').classList.add('hidden');
        document.getElementById('failure_reason').value = '';
        document.getElementById('fail_notes').value = '';
    }
</script>
