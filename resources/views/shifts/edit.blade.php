@extends('layouts.authenticated')

@section('title') Edit Shift - @endsection

@section('css')
<style>
.form-section {
    background: white;
    border-radius: 8px;
    padding: 30px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.section-header {
    border-bottom: 2px solid #667eea;
    padding-bottom: 10px;
    margin-bottom: 25px;
}
.rate-preview {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 8px;
    position: sticky;
    top: 80px;
}
.skill-tag {
    display: inline-block;
    background: #e1e8ed;
    padding: 5px 10px;
    border-radius: 15px;
    margin: 5px 5px 5px 0;
}
.skill-tag .remove-skill {
    cursor: pointer;
    margin-left: 5px;
    color: #dc3545;
}
</style>
@endsection

@section('content')
<div class="container py-5">
    <div class="row">
        <!-- Form -->
        <div class="col-lg-8">
            <h2 class="mb-4">Edit Shift</h2>

            @if($shift->status !== 'open')
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>Note:</strong> This shift is {{ $shift->status }}. Some fields cannot be edited.
                </div>
            @endif

            <form action="{{ route('shifts.update', $shift->id) }}" method="POST" id="shiftForm">
                @csrf
                @method('PUT')

                <!-- Basic Information -->
                <div class="form-section">
                    <h4 class="section-header">Basic Information</h4>

                    <div class="form-group">
                        <label>Shift Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $shift->title) }}" required {{ $shift->status !== 'open' ? 'readonly' : '' }}>
                        <small class="form-text text-muted">Give your shift a clear, descriptive title</small>
                    </div>

                    <div class="form-group">
                        <label>Industry <span class="text-danger">*</span></label>
                        <select name="industry" class="form-control" required {{ $shift->status !== 'open' ? 'disabled' : '' }}>
                            <option value="">Select Industry</option>
                            <option value="hospitality" {{ old('industry', $shift->industry) == 'hospitality' ? 'selected' : '' }}>Hospitality</option>
                            <option value="healthcare" {{ old('industry', $shift->industry) == 'healthcare' ? 'selected' : '' }}>Healthcare</option>
                            <option value="retail" {{ old('industry', $shift->industry) == 'retail' ? 'selected' : '' }}>Retail</option>
                            <option value="events" {{ old('industry', $shift->industry) == 'events' ? 'selected' : '' }}>Events</option>
                            <option value="warehouse" {{ old('industry', $shift->industry) == 'warehouse' ? 'selected' : '' }}>Warehouse</option>
                            <option value="professional" {{ old('industry', $shift->industry) == 'professional' ? 'selected' : '' }}>Professional</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="5" required>{{ old('description', $shift->description) }}</textarea>
                    </div>
                </div>

                <!-- Date & Time -->
                <div class="form-section">
                    <h4 class="section-header">Date & Time</h4>

                    @if(in_array($shift->status, ['in_progress', 'completed']))
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> Date and time cannot be changed for shifts that are in progress or completed.
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Shift Date <span class="text-danger">*</span></label>
                                <input type="date" name="shift_date" class="form-control" value="{{ old('shift_date', $shift->shift_date) }}" min="{{ date('Y-m-d') }}" required {{ in_array($shift->status, ['in_progress', 'completed']) ? 'readonly' : '' }}>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Urgency Level <span class="text-danger">*</span></label>
                                <select name="urgency_level" class="form-control" required>
                                    <option value="normal" {{ old('urgency_level', $shift->urgency_level) == 'normal' ? 'selected' : '' }}>Normal</option>
                                    <option value="urgent" {{ old('urgency_level', $shift->urgency_level) == 'urgent' ? 'selected' : '' }}>Urgent (+30% rate)</option>
                                    <option value="critical" {{ old('urgency_level', $shift->urgency_level) == 'critical' ? 'selected' : '' }}>Critical (+50% rate)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Start Time <span class="text-danger">*</span></label>
                                <input type="time" name="start_time" class="form-control" value="{{ old('start_time', $shift->start_time) }}" required {{ in_array($shift->status, ['in_progress', 'completed']) ? 'readonly' : '' }}>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>End Time <span class="text-danger">*</span></label>
                                <input type="time" name="end_time" class="form-control" value="{{ old('end_time', $shift->end_time) }}" required {{ in_array($shift->status, ['in_progress', 'completed']) ? 'readonly' : '' }}>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Location -->
                <div class="form-section">
                    <h4 class="section-header">Location</h4>

                    <div class="form-group">
                        <label>Address <span class="text-danger">*</span></label>
                        <input type="text" name="location_address" class="form-control" value="{{ old('location_address', $shift->location_address) }}" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>City <span class="text-danger">*</span></label>
                                <input type="text" name="location_city" class="form-control" value="{{ old('location_city', $shift->location_city) }}" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>State <span class="text-danger">*</span></label>
                                <input type="text" name="location_state" class="form-control" value="{{ old('location_state', $shift->location_state) }}" maxlength="2" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>ZIP Code <span class="text-danger">*</span></label>
                                <input type="text" name="location_zip" class="form-control" value="{{ old('location_zip', $shift->location_zip) }}" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Workers & Pay -->
                <div class="form-section">
                    <h4 class="section-header">Workers & Pay</h4>

                    @if($shift->filled_workers > 0)
                        <div class="alert alert-warning">
                            <i class="fa fa-exclamation-triangle"></i>
                            <strong>Note:</strong> {{ $shift->filled_workers }} worker(s) already assigned. Be careful when changing requirements.
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Number of Workers Needed <span class="text-danger">*</span></label>
                                <input type="number" name="required_workers" class="form-control" value="{{ old('required_workers', $shift->required_workers) }}" min="{{ $shift->filled_workers }}" required>
                                <small class="form-text text-muted">Currently filled: {{ $shift->filled_workers }}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Base Hourly Rate ($) <span class="text-danger">*</span></label>
                                <input type="number" name="base_rate" id="baseRate" class="form-control" value="{{ old('base_rate', $shift->base_rate) }}" min="7.25" step="0.01" required>
                                <small class="form-text text-muted">Minimum wage: $7.25/hr</small>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i>
                        <strong>Note:</strong> Changing the rate will affect future payouts but not existing assignments.
                    </div>
                </div>

                <!-- Requirements -->
                <div class="form-section">
                    <h4 class="section-header">Requirements</h4>

                    <div class="form-group">
                        <label>Required Skills</label>
                        <div class="input-group mb-2">
                            <input type="text" id="skillInput" class="form-control" placeholder="Type a skill and press Enter">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-primary" onclick="addSkill()">Add</button>
                            </div>
                        </div>
                        <div id="skillTags"></div>
                        <input type="hidden" name="required_skills" id="skillsHidden">
                    </div>

                    <div class="form-group">
                        <label>Required Certifications</label>
                        <div class="input-group mb-2">
                            <input type="text" id="certInput" class="form-control" placeholder="Type a certification and press Enter">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-primary" onclick="addCertification()">Add</button>
                            </div>
                        </div>
                        <div id="certTags"></div>
                        <input type="hidden" name="required_certifications" id="certsHidden">
                    </div>

                    <div class="form-group">
                        <label>Dress Code</label>
                        <input type="text" name="dress_code" class="form-control" value="{{ old('dress_code', $shift->dress_code) }}">
                    </div>

                    <div class="form-group">
                        <label>Special Instructions</label>
                        <textarea name="special_instructions" class="form-control" rows="3">{{ old('special_instructions', $shift->special_instructions) }}</textarea>
                    </div>
                </div>

                <!-- Submit -->
                <div class="text-right">
                    <a href="{{ route('business.shifts.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="fa fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- Preview Sidebar -->
        <div class="col-lg-4">
            <div class="rate-preview">
                <h5 class="mb-3">Cost Preview</h5>

                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Base Rate:</span>
                        <strong id="previewBaseRate">${{ number_format($shift->base_rate, 2) }}/hr</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Duration:</span>
                        <strong id="previewDuration">{{ $shift->duration_hours }} hours</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Workers:</span>
                        <strong id="previewWorkers">{{ $shift->required_workers }}</strong>
                    </div>
                </div>

                <hr style="border-color: rgba(255,255,255,0.3);">

                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <strong id="previewSubtotal">$0.00</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Platform Fee (15%):</span>
                        <strong id="previewFee">$0.00</strong>
                    </div>
                </div>

                <hr style="border-color: rgba(255,255,255,0.3);">

                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Estimated Total:</h5>
                    <h3 class="mb-0" id="previewTotal">$0.00</h3>
                </div>

                <small class="d-block mt-3" style="opacity: 0.8;">
                    Final cost calculated after shift completion based on actual hours worked
                </small>
            </div>

            <!-- Status Info -->
            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="card-title">Shift Status</h6>
                    <p>
                        <span class="badge badge-{{ $shift->status == 'open' ? 'success' : ($shift->status == 'completed' ? 'secondary' : 'primary') }}">
                            {{ ucfirst($shift->status) }}
                        </span>
                    </p>
                    <p class="small mb-0">
                        <strong>Filled:</strong> {{ $shift->filled_workers }}/{{ $shift->required_workers }} workers<br>
                        <strong>Applications:</strong> {{ $shift->applications_count ?? 0 }}<br>
                        <strong>Posted:</strong> {{ \Carbon\Carbon::parse($shift->created_at)->diffForHumans() }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
// Initialize with existing data
let skills = {!! json_encode($shift->required_skills ?? []) !!} || [];
let certifications = {!! json_encode($shift->required_certifications ?? []) !!} || [];

// Initial render
renderSkills();
renderCertifications();

// Skills Management
function addSkill() {
    const input = document.getElementById('skillInput');
    const skill = input.value.trim();

    if (skill && !skills.includes(skill)) {
        skills.push(skill);
        renderSkills();
        input.value = '';
    }
}

function removeSkill(skill) {
    skills = skills.filter(s => s !== skill);
    renderSkills();
}

function renderSkills() {
    const container = document.getElementById('skillTags');
    container.innerHTML = skills.map(skill =>
        `<span class="skill-tag">${skill}<span class="remove-skill" onclick="removeSkill('${skill}')">&times;</span></span>`
    ).join('');
    document.getElementById('skillsHidden').value = JSON.stringify(skills);
}

// Certifications Management
function addCertification() {
    const input = document.getElementById('certInput');
    const cert = input.value.trim();

    if (cert && !certifications.includes(cert)) {
        certifications.push(cert);
        renderCertifications();
        input.value = '';
    }
}

function removeCertification(cert) {
    certifications = certifications.filter(c => c !== cert);
    renderCertifications();
}

function renderCertifications() {
    const container = document.getElementById('certTags');
    container.innerHTML = certifications.map(cert =>
        `<span class="skill-tag">${cert}<span class="remove-skill" onclick="removeCertification('${cert}')">&times;</span></span>`
    ).join('');
    document.getElementById('certsHidden').value = JSON.stringify(certifications);
}

// Enter key to add
document.getElementById('skillInput').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        addSkill();
    }
});

document.getElementById('certInput').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        addCertification();
    }
});

// Cost Calculator
function updatePreview() {
    const baseRate = parseFloat(document.querySelector('[name="base_rate"]').value) || 0;
    const workers = parseInt(document.querySelector('[name="required_workers"]').value) || 1;
    const startTime = document.querySelector('[name="start_time"]').value;
    const endTime = document.querySelector('[name="end_time"]').value;

    // Calculate duration
    let duration = 0;
    if (startTime && endTime) {
        const start = new Date(`2000-01-01T${startTime}`);
        const end = new Date(`2000-01-01T${endTime}`);
        duration = (end - start) / (1000 * 60 * 60);
        if (duration < 0) duration += 24; // Handle overnight shifts
    }

    const subtotal = baseRate * duration * workers;
    const platformFee = subtotal * 0.15;
    const total = subtotal + platformFee;

    document.getElementById('previewBaseRate').textContent = `$${baseRate.toFixed(2)}/hr`;
    document.getElementById('previewDuration').textContent = `${duration.toFixed(1)} hours`;
    document.getElementById('previewWorkers').textContent = workers;
    document.getElementById('previewSubtotal').textContent = `$${subtotal.toFixed(2)}`;
    document.getElementById('previewFee').textContent = `$${platformFee.toFixed(2)}`;
    document.getElementById('previewTotal').textContent = `$${total.toFixed(2)}`;
}

// Update preview on input change
document.querySelectorAll('[name="base_rate"], [name="required_workers"], [name="start_time"], [name="end_time"]').forEach(input => {
    input.addEventListener('input', updatePreview);
    input.addEventListener('change', updatePreview);
});

// Initial calculation
updatePreview();
</script>
@endsection
