@extends('layouts.authenticated')

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

                            <div class="mb-4 space-y-2">
                                <x-ui.label class="after:content-['*'] after:ml-0.5 after:text-destructive"
                                    value="Overall Rating" />
                                <div class="rating-input">
                                    @for($i = 5; $i >= 1; $i--)
                                        <input type="radio" name="overall" value="{{ $i }}" id="overall_{{ $i }}" required>
                                        <label for="overall_{{ $i }}" class="star">⭐</label>
                                    @endfor
                                </div>
                                @error('overall')
                                    <div class="text-sm text-destructive">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 space-y-2">
                                <x-ui.label for="communication" value="Communication" />
                                <x-ui.select name="communication" id="communication">
                                    <option value="">Select rating</option>
                                    @for($i = 5; $i >= 1; $i--)
                                        <option value="{{ $i }}">{{ $i }} stars</option>
                                    @endfor
                                </x-ui.select>
                            </div>

                            <div class="mb-3 space-y-2">
                                <x-ui.label for="work_environment" value="Work Environment" />
                                <x-ui.select name="work_environment" id="work_environment">
                                    <option value="">Select rating</option>
                                    @for($i = 5; $i >= 1; $i--)
                                        <option value="{{ $i }}">{{ $i }} stars</option>
                                    @endfor
                                </x-ui.select>
                            </div>

                            <div class="mb-3 space-y-2">
                                <x-ui.label class="after:content-['*'] after:ml-0.5 after:text-destructive"
                                    value="Would You Work Again?" />
                                <div class="flex gap-4">
                                    <div class="flex items-center space-x-2">
                                        <input type="radio" name="would_work_again" value="1" id="yes" required
                                            class="h-4 w-4 border-gray-300 text-primary focus:ring-primary">
                                        <label for="yes"
                                            class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Yes</label>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <input type="radio" name="would_work_again" value="0" id="no" required
                                            class="h-4 w-4 border-gray-300 text-primary focus:ring-primary">
                                        <label for="no"
                                            class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">No</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3 space-y-2">
                                <x-ui.label for="comment" value="Comment (Optional)" />
                                <x-ui.textarea name="comment" id="comment" rows="4" maxlength="500"
                                    placeholder="Share your experience..."></x-ui.textarea>
                                <p class="text-xs text-muted-foreground">Max 500 characters</p>
                            </div>

                            <div class="flex gap-2">
                                <x-ui.button type="submit">Submit Rating</x-ui.button>
                                <x-ui.button variant="secondary" tag="a"
                                    href="{{ route('worker.assignments.show', $assignment->id) }}">Cancel</x-ui.button>
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

        .rating-input input:checked~label,
        .rating-input label:hover,
        .rating-input label:hover~label {
            color: #ffc107;
        }
    </style>
@endsection