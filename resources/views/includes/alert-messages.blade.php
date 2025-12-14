@if (session()->has('success_message') || session('status'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-1"></i>
        {{ session('success_message') ?? session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if (session()->has('error_message') || $errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle me-1"></i>
        {{ session('error_message') ?? trans('auth.error_desc') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif