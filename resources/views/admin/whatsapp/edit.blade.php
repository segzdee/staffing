@extends('layouts.admin-dashboard')

@section('title', 'Edit WhatsApp Template: ' . $template->name)

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Edit WhatsApp Template</h1>
                    <p class="text-muted mb-0">Update template: {{ $template->name }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.whatsapp.show', $template) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Template
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Template Details</h5>
                    <div>
                        @if($template->status === 'approved')
                            <span class="badge bg-success">Approved</span>
                        @elseif($template->status === 'pending')
                            <span class="badge bg-warning">Pending</span>
                        @else
                            <span class="badge bg-danger">Rejected</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if($template->status === 'approved')
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Note:</strong> Editing an approved template may require re-approval from Meta.
                        Changes to the template content will need to be synced with your Meta Business account.
                    </div>
                    @endif

                    <form action="{{ route('admin.whatsapp.update', $template) }}" method="POST" id="templateForm">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Template Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       id="name" name="name" value="{{ old('name', $template->name) }}" required
                                       placeholder="e.g., shift_reminder">
                                <div class="form-text">Internal name for this template (lowercase, underscores)</div>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="template_id" class="form-label">Meta Template ID <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('template_id') is-invalid @enderror"
                                       id="template_id" name="template_id" value="{{ old('template_id', $template->template_id) }}" required
                                       placeholder="e.g., shift_reminder_v1">
                                <div class="form-text">The template ID registered with Meta Business API</div>
                                @error('template_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="language" class="form-label">Language <span class="text-danger">*</span></label>
                                <select class="form-select @error('language') is-invalid @enderror"
                                        id="language" name="language" required>
                                    <option value="">Select Language</option>
                                    <option value="en" {{ old('language', $template->language) == 'en' ? 'selected' : '' }}>English</option>
                                    <option value="en_US" {{ old('language', $template->language) == 'en_US' ? 'selected' : '' }}>English (US)</option>
                                    <option value="en_GB" {{ old('language', $template->language) == 'en_GB' ? 'selected' : '' }}>English (UK)</option>
                                    <option value="es" {{ old('language', $template->language) == 'es' ? 'selected' : '' }}>Spanish</option>
                                    <option value="fr" {{ old('language', $template->language) == 'fr' ? 'selected' : '' }}>French</option>
                                    <option value="de" {{ old('language', $template->language) == 'de' ? 'selected' : '' }}>German</option>
                                    <option value="pt_BR" {{ old('language', $template->language) == 'pt_BR' ? 'selected' : '' }}>Portuguese (Brazil)</option>
                                    <option value="hi" {{ old('language', $template->language) == 'hi' ? 'selected' : '' }}>Hindi</option>
                                    <option value="ar" {{ old('language', $template->language) == 'ar' ? 'selected' : '' }}>Arabic</option>
                                    <option value="zh_CN" {{ old('language', $template->language) == 'zh_CN' ? 'selected' : '' }}>Chinese (Simplified)</option>
                                </select>
                                @error('language')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select @error('category') is-invalid @enderror"
                                        id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="utility" {{ old('category', $template->category) == 'utility' ? 'selected' : '' }}>Utility</option>
                                    <option value="marketing" {{ old('category', $template->category) == 'marketing' ? 'selected' : '' }}>Marketing</option>
                                    <option value="authentication" {{ old('category', $template->category) == 'authentication' ? 'selected' : '' }}>Authentication</option>
                                </select>
                                <div class="form-text">
                                    <strong>Utility:</strong> Transactional updates<br>
                                    <strong>Marketing:</strong> Promotional messages<br>
                                    <strong>Authentication:</strong> OTP/verification
                                </div>
                                @error('category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="mb-3">Header (Optional)</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="header_type" class="form-label">Header Type</label>
                                <select class="form-select @error('header_type') is-invalid @enderror"
                                        id="header_type" name="header_type">
                                    <option value="">No Header</option>
                                    <option value="text" {{ old('header_type', $template->header['type'] ?? '') == 'text' ? 'selected' : '' }}>Text</option>
                                    <option value="image" {{ old('header_type', $template->header['type'] ?? '') == 'image' ? 'selected' : '' }}>Image</option>
                                    <option value="document" {{ old('header_type', $template->header['type'] ?? '') == 'document' ? 'selected' : '' }}>Document</option>
                                    <option value="video" {{ old('header_type', $template->header['type'] ?? '') == 'video' ? 'selected' : '' }}>Video</option>
                                </select>
                                @error('header_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-8 mb-3" id="headerContentGroup" style="{{ ($template->header['type'] ?? '') ? '' : 'display: none;' }}">
                                <label for="header_content" class="form-label">Header Content</label>
                                <input type="text" class="form-control @error('header_content') is-invalid @enderror"
                                       id="header_content" name="header_content"
                                       value="{{ old('header_content', $template->header['content'] ?? '') }}"
                                       placeholder="Header text or media URL">
                                <div class="form-text" id="headerHelp">Enter header text (supports {{1}} placeholder)</div>
                                @error('header_content')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="mb-3">
                            <label for="content" class="form-label">Message Body <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('content') is-invalid @enderror"
                                      id="content" name="content" rows="6" required
                                      placeholder="Enter your message template with {{1}}, {{2}}, etc. for variables">{{ old('content', $template->content) }}</textarea>
                            <div class="form-text">
                                Use placeholders like <code>{{1}}</code>, <code>{{2}}</code> for dynamic content.
                                Example: "Hi {{1}}, your shift at {{2}} starts at {{3}}."
                            </div>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="footer" class="form-label">Footer (Optional)</label>
                            <input type="text" class="form-control @error('footer') is-invalid @enderror"
                                   id="footer" name="footer"
                                   value="{{ old('footer', $template->footer['text'] ?? '') }}"
                                   placeholder="e.g., Reply STOP to unsubscribe" maxlength="60">
                            <div class="form-text">Max 60 characters. Often used for opt-out instructions.</div>
                            @error('footer')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr class="my-4">

                        <h6 class="mb-3">Buttons (Optional)</h6>
                        <div id="buttonsContainer">
                            @if($template->buttons)
                                @foreach($template->buttons as $index => $button)
                                <div class="row mb-2 button-row" data-index="{{ $index + 1 }}">
                                    <div class="col-md-4">
                                        <select class="form-select min-h-[40px] button-type" name="buttons[{{ $index + 1 }}][type]">
                                            <option value="QUICK_REPLY" {{ ($button['type'] ?? '') == 'QUICK_REPLY' ? 'selected' : '' }}>Quick Reply</option>
                                            <option value="URL" {{ ($button['type'] ?? '') == 'URL' ? 'selected' : '' }}>URL</option>
                                            <option value="PHONE_NUMBER" {{ ($button['type'] ?? '') == 'PHONE_NUMBER' ? 'selected' : '' }}>Phone Number</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control min-h-[40px] button-text"
                                               name="buttons[{{ $index + 1 }}][text]"
                                               value="{{ $button['text'] ?? '' }}"
                                               placeholder="Button text" maxlength="25">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-outline-danger min-h-[40px] min-w-[40px] p-2 remove-button">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                @endforeach
                            @endif
                        </div>
                        <button type="button" class="btn btn-outline-secondary min-h-[40px] py-2 px-4" id="addButton">
                            <i class="fas fa-plus me-1"></i>Add Button
                        </button>

                        <hr class="my-4">

                        @if($template->status === 'approved')
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                       {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Template is active and can be used for sending messages
                                </label>
                            </div>
                        </div>
                        <hr class="my-4">
                        @endif

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.whatsapp.show', $template) }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Template
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Preview</h5>
                </div>
                <div class="card-body">
                    <div class="whatsapp-preview">
                        <div class="whatsapp-message">
                            <div class="whatsapp-header" id="previewHeader" style="display: none;"></div>
                            <div class="whatsapp-body" id="previewBody">
                                <span class="text-muted">Enter message content to see preview</span>
                            </div>
                            <div class="whatsapp-footer" id="previewFooter" style="display: none;"></div>
                            <div class="whatsapp-buttons" id="previewButtons" style="display: none;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Template Status</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5">Status:</dt>
                        <dd class="col-7">
                            @if($template->status === 'approved')
                                <span class="badge bg-success">Approved</span>
                            @elseif($template->status === 'pending')
                                <span class="badge bg-warning">Pending</span>
                            @else
                                <span class="badge bg-danger">Rejected</span>
                            @endif
                        </dd>
                        <dt class="col-5">Created:</dt>
                        <dd class="col-7">{{ $template->created_at->format('M d, Y') }}</dd>
                        <dt class="col-5">Updated:</dt>
                        <dd class="col-7">{{ $template->updated_at->format('M d, Y') }}</dd>
                        @if($template->approved_at)
                        <dt class="col-5">Approved:</dt>
                        <dd class="col-7">{{ $template->approved_at->format('M d, Y') }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Template Guidelines</h5>
                </div>
                <div class="card-body">
                    <ul class="small mb-0">
                        <li class="mb-2">Templates must be approved by Meta before use</li>
                        <li class="mb-2">Avoid promotional language in utility templates</li>
                        <li class="mb-2">Use clear, professional language</li>
                        <li class="mb-2">Include opt-out instructions for marketing</li>
                        <li class="mb-2">Placeholders must be numbered sequentially</li>
                        <li>Authentication templates should only contain OTP code</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.whatsapp-preview {
    background-color: #e5ddd5;
    padding: 1rem;
    border-radius: 8px;
    min-height: 200px;
}
.whatsapp-message {
    background-color: #dcf8c6;
    border-radius: 8px;
    padding: 8px 12px;
    max-width: 100%;
    box-shadow: 0 1px 0.5px rgba(0,0,0,0.13);
}
.whatsapp-header {
    font-weight: bold;
    margin-bottom: 4px;
    padding-bottom: 4px;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}
.whatsapp-body {
    white-space: pre-wrap;
    word-wrap: break-word;
}
.whatsapp-footer {
    font-size: 0.75rem;
    color: #667781;
    margin-top: 4px;
}
.whatsapp-buttons {
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid rgba(0,0,0,0.1);
}
.whatsapp-button {
    display: block;
    text-align: center;
    padding: 8px;
    color: #00a5f4;
    font-size: 0.875rem;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}
.whatsapp-button:last-child {
    border-bottom: none;
}
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const headerType = document.getElementById('header_type');
    const headerContentGroup = document.getElementById('headerContentGroup');
    const headerHelp = document.getElementById('headerHelp');
    const headerContent = document.getElementById('header_content');
    const content = document.getElementById('content');
    const footer = document.getElementById('footer');
    const buttonsContainer = document.getElementById('buttonsContainer');
    const addButton = document.getElementById('addButton');

    // Preview elements
    const previewHeader = document.getElementById('previewHeader');
    const previewBody = document.getElementById('previewBody');
    const previewFooter = document.getElementById('previewFooter');
    const previewButtons = document.getElementById('previewButtons');

    let buttonCount = buttonsContainer.querySelectorAll('.button-row').length;

    // Header type change handler
    headerType.addEventListener('change', function() {
        const type = this.value;
        if (type) {
            headerContentGroup.style.display = 'block';
            if (type === 'text') {
                headerHelp.textContent = 'Enter header text (supports {{1}} placeholder)';
                headerContent.placeholder = 'Header text';
            } else {
                headerHelp.textContent = 'Enter the media URL for the ' + type;
                headerContent.placeholder = type.charAt(0).toUpperCase() + type.slice(1) + ' URL';
            }
        } else {
            headerContentGroup.style.display = 'none';
        }
        updatePreview();
    });

    // Add button handler
    addButton.addEventListener('click', function() {
        if (buttonCount >= 3) {
            alert('Maximum 3 buttons allowed per template');
            return;
        }

        buttonCount++;
        const buttonHtml = `
            <div class="row mb-2 button-row" data-index="${buttonCount}">
                <div class="col-md-4">
                    <select class="form-select min-h-[40px] button-type" name="buttons[${buttonCount}][type]">
                        <option value="QUICK_REPLY">Quick Reply</option>
                        <option value="URL">URL</option>
                        <option value="PHONE_NUMBER">Phone Number</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control min-h-[40px] button-text"
                           name="buttons[${buttonCount}][text]" placeholder="Button text" maxlength="25">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger min-h-[40px] min-w-[40px] p-2 remove-button">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
        buttonsContainer.insertAdjacentHTML('beforeend', buttonHtml);
        updatePreview();
    });

    // Remove button handler
    buttonsContainer.addEventListener('click', function(e) {
        if (e.target.closest('.remove-button')) {
            e.target.closest('.button-row').remove();
            buttonCount--;
            updatePreview();
        }
    });

    // Content change handlers
    content.addEventListener('input', updatePreview);
    footer.addEventListener('input', updatePreview);
    headerContent.addEventListener('input', updatePreview);
    buttonsContainer.addEventListener('input', updatePreview);

    function updatePreview() {
        // Header
        if (headerType.value === 'text' && headerContent.value) {
            previewHeader.textContent = headerContent.value;
            previewHeader.style.display = 'block';
        } else if (headerType.value && headerType.value !== 'text') {
            previewHeader.innerHTML = `<i class="fas fa-${headerType.value === 'image' ? 'image' : headerType.value === 'video' ? 'video' : 'file'}"></i> [${headerType.value.toUpperCase()}]`;
            previewHeader.style.display = 'block';
        } else {
            previewHeader.style.display = 'none';
        }

        // Body
        if (content.value) {
            previewBody.textContent = content.value;
        } else {
            previewBody.innerHTML = '<span class="text-muted">Enter message content to see preview</span>';
        }

        // Footer
        if (footer.value) {
            previewFooter.textContent = footer.value;
            previewFooter.style.display = 'block';
        } else {
            previewFooter.style.display = 'none';
        }

        // Buttons
        const buttonRows = buttonsContainer.querySelectorAll('.button-row');
        if (buttonRows.length > 0) {
            let buttonsHtml = '';
            buttonRows.forEach(row => {
                const text = row.querySelector('.button-text').value || 'Button';
                buttonsHtml += `<div class="whatsapp-button">${text}</div>`;
            });
            previewButtons.innerHTML = buttonsHtml;
            previewButtons.style.display = 'block';
        } else {
            previewButtons.style.display = 'none';
        }
    }

    // Initialize preview
    updatePreview();
});
</script>
@endpush
@endsection
