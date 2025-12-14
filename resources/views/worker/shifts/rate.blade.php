@extends('layouts.app')

@section('title', 'Rate Business')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Rate Your Experience</h4>
                    <p class="mb-0 text-muted">Shift: {{ $assignment->shift->title }}</p>
                </div>
                <div class="card-body">
                    <form action="{{ route('worker.shifts.rate.store', $assignment->id) }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label">Overall Rating <span class="text-danger">*</span></label>
                            <div class="rating-input">
                                @for($i = 5; $i >= 1; $i--)
                                    <input type="radio" name="overall" value="{{ $i }}" id="overall_{{ $i }}" required>
                                    <label for="overall_{{ $i }}" class="star">⭐</label>
                                @endfor
                            </div>
                            @error('overall')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Communication</label>
                            <select name="communication" class="form-select">
                                <option value="">Select rating</option>
                                @for($i = 5; $i >= 1; $i--)
                                    <option value="{{ $i }}">{{ $i }} stars</option>
                                @endfor
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Work Environment</label>
                            <select name="work_environment" class="form-select">
                                <option value="">Select rating</option>
                                @for($i = 5; $i >= 1; $i--)
                                    <option value="{{ $i }}">{{ $i }} stars</option>
                                @endfor
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Would You Work Again? <span class="text-danger">*</span></label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="would_work_again" value="1" id="yes" required>
                                    <label class="form-check-label" for="yes">Yes</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="would_work_again" value="0" id="no" required>
                                    <label class="form-check-label" for="no">No</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Comment (Optional)</label>
                            <textarea name="comment" class="form-control" rows="4" maxlength="500" placeholder="Share your experience..."></textarea>
                            <small class="text-muted">Max 500 characters</small>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Submit Rating</button>
                            <a href="{{ route('worker.assignments.show', $assignment->id) }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.rating-input {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
}
.rating-input input {
    display: none;
}
.rating-input label {
    font-size: 2rem;
    cursor: pointer;
    color: #ddd;
    transition: color 0.2s;
}
.rating-input input:checked ~ label,
.rating-input label:hover,
.rating-input label:hover ~ label {
    color: #ffc107;
}
</style>
@endsection
