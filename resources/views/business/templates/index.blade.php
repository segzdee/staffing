@extends('layouts.authenticated')

@section('title') Shift Templates - @endsection

@section('css')
<style>
.template-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #e1e8ed;
    transition: all 0.3s ease;
}
.template-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}
.template-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 15px;
}
.auto-renew-badge {
    background: #17a2b8;
    color: white;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 600;
}
.recurrence-info {
    background: #f0f4ff;
    padding: 10px;
    border-radius: 8px;
    margin-top: 10px;
}
</style>
@endsection

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-lg-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Shift Templates</h2>
                    <p class="text-muted mb-0">Create reusable templates to quickly post recurring shifts</p>
                </div>
                <button class="btn btn-primary" data-toggle="modal" data-target="#createTemplateModal">
                    <i class="fa fa-plus"></i> Create Template
                </button>
            </div>

            <!-- Info Card -->
            <div class="alert alert-info mb-4">
                <i class="fa fa-lightbulb-o"></i>
                <strong>Tip:</strong> Templates with auto-renewal will automatically create new shifts based on your recurrence pattern. This saves time for regularly scheduled shifts.
            </div>

            <!-- Templates List -->
            @forelse($templates as $template)
            <div class="template-card">
                <div class="template-header">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-2">
                            <h5 class="mb-0 mr-2">{{ $template->template_name }}</h5>
                            @if($template->auto_renew)
                                <span class="auto-renew-badge">
                                    <i class="fa fa-refresh"></i> AUTO-RENEW
                                </span>
                            @endif
                            @if(!$template->is_active)
                                <span class="badge badge-secondary ml-2">INACTIVE</span>
                            @endif
                        </div>

                        <div class="text-muted small mb-2">
                            <i class="fa fa-briefcase"></i> {{ ucfirst(str_replace('_', ' ', $template->industry)) }}
                        </div>

                        <p class="mb-2">{{ Str::limit($template->description, 150) }}</p>

                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="badge badge-light mr-2">
                                <i class="fa fa-clock-o"></i> {{ \Carbon\Carbon::parse($template->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($template->end_time)->format('g:i A') }}
                            </span>
                            <span class="badge badge-light mr-2">
                                <i class="fa fa-users"></i> {{ $template->required_workers }} workers
                            </span>
                            <span class="badge badge-light mr-2">
                                <i class="fa fa-dollar"></i> ${{ number_format($template->base_rate, 2) }}/hr
                            </span>
                            <span class="badge badge-light">
                                <i class="fa fa-map-marker"></i> {{ $template->location_city }}, {{ $template->location_state }}
                            </span>
                        </div>

                        @if($template->auto_renew && $template->recurrence_pattern)
                        <div class="recurrence-info">
                            <small class="text-muted">
                                <i class="fa fa-repeat"></i>
                                <strong>Recurrence:</strong>
                                {{ ucfirst($template->recurrence_pattern) }}
                                @if($template->recurrence_days)
                                    on {{ implode(', ', array_map('ucfirst', json_decode($template->recurrence_days, true))) }}
                                @endif
                            </small>
                        </div>
                        @endif

                        <div class="mt-2">
                            <small class="text-muted">
                                Created {{ $template->created_at->diffForHumans() }} •
                                Used {{ $template->shifts()->count() }} times
                            </small>
                        </div>
                    </div>

                    <div class="text-right" style="min-width: 200px;">
                        <form action="{{ route('business.templates.createShifts', $template->id) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="button" class="btn btn-success btn-block" onclick="showBulkCreateModal({{ $template->id }})">
                                <i class="fa fa-calendar-plus-o"></i> Create Shifts
                            </button>
                        </form>

                        <div class="btn-group btn-block mb-2">
                            <button class="btn btn-outline-primary" onclick="editTemplate({{ $template->id }})">
                                <i class="fa fa-edit"></i> Edit
                            </button>
                            <div class="btn-group">
                                <button class="btn btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
                                    <i class="fa fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <form action="{{ route('business.templates.duplicate', $template->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="fa fa-copy"></i> Duplicate
                                        </button>
                                    </form>
                                    @if($template->is_active)
                                        <form action="{{ route('business.templates.deactivate', $template->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                <i class="fa fa-pause"></i> Deactivate
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('business.templates.activate', $template->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                <i class="fa fa-play"></i> Activate
                                            </button>
                                        </form>
                                    @endif
                                    <div class="dropdown-divider"></div>
                                    <form action="{{ route('business.templates.delete', $template->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this template?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="fa fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-5">
                <i class="fa fa-copy fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No templates yet</h5>
                <p class="text-muted">Create templates for shifts you post regularly to save time</p>
                <button class="btn btn-primary" data-toggle="modal" data-target="#createTemplateModal">
                    <i class="fa fa-plus"></i> Create Your First Template
                </button>
            </div>
            @endforelse

            <!-- Pagination -->
            @if($templates->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $templates->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Create Template Modal - Mobile Optimized -->
<div class="modal fade" id="createTemplateModal" tabindex="-1" role="dialog" aria-labelledby="createTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content max-h-[100vh] sm:max-h-[90vh] flex flex-col rounded-none sm:rounded-lg overflow-hidden">
            <div class="modal-header flex-shrink-0 px-4 py-3 sm:px-5 sm:py-4 border-b border-gray-200 bg-white">
                <h5 class="modal-title text-lg font-semibold text-gray-900 m-0" id="createTemplateModalLabel">Create Shift Template</h5>
                <button
                    type="button"
                    class="close min-h-[44px] min-w-[44px] sm:min-h-[36px] sm:min-w-[36px] flex items-center justify-center text-gray-400 hover:text-gray-500 active:text-gray-600 touch-manipulation rounded-lg hover:bg-gray-100 -mr-2 transition-colors"
                    data-dismiss="modal"
                    aria-label="Close"
                >
                    <span aria-hidden="true" class="text-2xl leading-none">&times;</span>
                </button>
            </div>
            <form action="{{ route('business.templates.store') }}" method="POST">
                @csrf
                <div class="modal-body flex-1 overflow-y-auto overscroll-contain px-4 py-4 sm:px-5 bg-white">
                    <div class="form-group">
                        <label>Template Name <span class="text-danger">*</span></label>
                        <input type="text" name="template_name" class="form-control" placeholder="e.g., Weekend Server Shift" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Industry <span class="text-danger">*</span></label>
                                <select name="industry" class="form-control" required>
                                    <option value="hospitality">Hospitality</option>
                                    <option value="healthcare">Healthcare</option>
                                    <option value="retail">Retail</option>
                                    <option value="events">Events</option>
                                    <option value="warehouse">Warehouse</option>
                                    <option value="professional">Professional</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Number of Workers <span class="text-danger">*</span></label>
                                <input type="number" name="required_workers" class="form-control" value="1" min="1" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Shift description..."></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Start Time <span class="text-danger">*</span></label>
                                <input type="time" name="start_time" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>End Time <span class="text-danger">*</span></label>
                                <input type="time" name="end_time" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Base Hourly Rate ($) <span class="text-danger">*</span></label>
                        <input type="number" name="base_rate" class="form-control" value="15.00" min="7.25" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label>Location City <span class="text-danger">*</span></label>
                        <input type="text" name="location_city" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Location State <span class="text-danger">*</span></label>
                        <input type="text" name="location_state" class="form-control" maxlength="2" required>
                    </div>

                    <hr>
                    <h6 class="mb-3">Auto-Renewal Settings</h6>

                    <div class="form-check mb-3">
                        <input type="checkbox" name="auto_renew" class="form-check-input" id="autoRenewCheck" onchange="toggleRecurrence()">
                        <label class="form-check-label" for="autoRenewCheck">
                            Enable auto-renewal (automatically create shifts based on schedule)
                        </label>
                    </div>

                    <div id="recurrenceSettings" style="display: none;">
                        <div class="form-group">
                            <label>Recurrence Pattern</label>
                            <select name="recurrence_pattern" class="form-control">
                                <option value="weekly">Weekly</option>
                                <option value="biweekly">Bi-weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Days of Week</label>
                            <div class="d-flex flex-wrap">
                                <div class="form-check mr-3">
                                    <input type="checkbox" name="recurrence_days[]" value="monday" class="form-check-input" id="mon">
                                    <label class="form-check-label" for="mon">Mon</label>
                                </div>
                                <div class="form-check mr-3">
                                    <input type="checkbox" name="recurrence_days[]" value="tuesday" class="form-check-input" id="tue">
                                    <label class="form-check-label" for="tue">Tue</label>
                                </div>
                                <div class="form-check mr-3">
                                    <input type="checkbox" name="recurrence_days[]" value="wednesday" class="form-check-input" id="wed">
                                    <label class="form-check-label" for="wed">Wed</label>
                                </div>
                                <div class="form-check mr-3">
                                    <input type="checkbox" name="recurrence_days[]" value="thursday" class="form-check-input" id="thu">
                                    <label class="form-check-label" for="thu">Thu</label>
                                </div>
                                <div class="form-check mr-3">
                                    <input type="checkbox" name="recurrence_days[]" value="friday" class="form-check-input" id="fri">
                                    <label class="form-check-label" for="fri">Fri</label>
                                </div>
                                <div class="form-check mr-3">
                                    <input type="checkbox" name="recurrence_days[]" value="saturday" class="form-check-input" id="sat">
                                    <label class="form-check-label" for="sat">Sat</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="recurrence_days[]" value="sunday" class="form-check-input" id="sun">
                                    <label class="form-check-label" for="sun">Sun</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-shrink-0 px-4 py-3 sm:px-5 sm:py-4 border-t border-gray-200 bg-gray-50 flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 justify-end pb-[calc(0.75rem+env(safe-area-inset-bottom))] sm:pb-4">
                    <button type="button" class="btn btn-secondary w-full sm:w-auto min-h-[44px] sm:min-h-[40px] touch-manipulation" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary w-full sm:w-auto min-h-[44px] sm:min-h-[40px] touch-manipulation">Create Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Create Modal - Mobile Optimized -->
<div class="modal fade" id="bulkCreateModal" tabindex="-1" role="dialog" aria-labelledby="bulkCreateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content max-h-[100vh] sm:max-h-[90vh] flex flex-col rounded-none sm:rounded-lg overflow-hidden">
            <div class="modal-header flex-shrink-0 px-4 py-3 sm:px-5 sm:py-4 border-b border-gray-200 bg-white">
                <h5 class="modal-title text-lg font-semibold text-gray-900 m-0" id="bulkCreateModalLabel">Create Shifts from Template</h5>
                <button
                    type="button"
                    class="close min-h-[44px] min-w-[44px] sm:min-h-[36px] sm:min-w-[36px] flex items-center justify-center text-gray-400 hover:text-gray-500 active:text-gray-600 touch-manipulation rounded-lg hover:bg-gray-100 -mr-2 transition-colors"
                    data-dismiss="modal"
                    aria-label="Close"
                >
                    <span aria-hidden="true" class="text-2xl leading-none">&times;</span>
                </button>
            </div>
            <form id="bulkCreateForm" method="POST">
                @csrf
                <div class="modal-body flex-1 overflow-y-auto overscroll-contain px-4 py-4 sm:px-5 bg-white">
                    <div class="form-group mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="date" name="start_date" class="form-control min-h-[44px] sm:min-h-[40px] touch-manipulation" required>
                    </div>
                    <div class="form-group mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" name="end_date" class="form-control min-h-[44px] sm:min-h-[40px] touch-manipulation" required>
                    </div>
                    <div class="alert alert-info flex items-start gap-2">
                        <i class="fa fa-info-circle mt-0.5 flex-shrink-0"></i>
                        <span>Shifts will be created based on the template's recurrence pattern within this date range.</span>
                    </div>
                </div>
                <div class="modal-footer flex-shrink-0 px-4 py-3 sm:px-5 sm:py-4 border-t border-gray-200 bg-gray-50 flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 justify-end pb-[calc(0.75rem+env(safe-area-inset-bottom))] sm:pb-4">
                    <button type="button" class="btn btn-secondary w-full sm:w-auto min-h-[44px] sm:min-h-[40px] touch-manipulation" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success w-full sm:w-auto min-h-[44px] sm:min-h-[40px] touch-manipulation">Create Shifts</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
function toggleRecurrence() {
    const checkbox = document.getElementById('autoRenewCheck');
    const settings = document.getElementById('recurrenceSettings');
    settings.style.display = checkbox.checked ? 'block' : 'none';
}

function showBulkCreateModal(templateId) {
    const form = document.getElementById('bulkCreateForm');
    form.action = `/business/templates/${templateId}/create-shifts`;
    $('#bulkCreateModal').modal('show');
}

function editTemplate(templateId) {
    window.location.href = `/business/templates/${templateId}/edit`;
}
</script>
@endsection
