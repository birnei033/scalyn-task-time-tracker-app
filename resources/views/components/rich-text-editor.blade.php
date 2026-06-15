@props([
    'name',
    'label',
    'value' => null,
    'placeholder' => 'Write here...',
    'rows' => 6,
])

@php
    $inputId = $attributes->get('id', $name);
    $oldValue = old($name, $value);
    $hasError = $errors->has($name);
@endphp

<div class="rich-text-editor" data-rich-editor>
    @if ($label)
        <label for="{{ $inputId }}" class="form-label">{{ $label }}</label>
    @endif

    <input type="hidden" id="{{ $inputId }}" name="{{ $name }}" value="{{ old($name, $value) }}">

    <div class="rich-text-toolbar">
        <button type="button" class="btn btn-outline-secondary btn-sm icon-only-btn" data-rich-editor-command="bold" aria-label="Bold" title="Bold">
            <i class="bi bi-type-bold" aria-hidden="true"></i>
            <span class="visually-hidden">Bold</span>
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm icon-only-btn" data-rich-editor-command="italic" aria-label="Italic" title="Italic">
            <i class="bi bi-type-italic" aria-hidden="true"></i>
            <span class="visually-hidden">Italic</span>
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm icon-only-btn" data-rich-editor-command="underline" aria-label="Underline" title="Underline">
            <i class="bi bi-type-underline" aria-hidden="true"></i>
            <span class="visually-hidden">Underline</span>
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm icon-only-btn" data-rich-editor-command="insertUnorderedList" aria-label="Bulleted list" title="Bulleted list">
            <i class="bi bi-list-ul" aria-hidden="true"></i>
            <span class="visually-hidden">Bulleted list</span>
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm icon-only-btn" data-rich-editor-command="insertOrderedList" aria-label="Numbered list" title="Numbered list">
            <i class="bi bi-list-ol" aria-hidden="true"></i>
            <span class="visually-hidden">Numbered list</span>
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm icon-only-btn" data-rich-editor-command="formatBlock" data-rich-editor-value="blockquote" aria-label="Quote" title="Quote">
            <i class="bi bi-chat-quote" aria-hidden="true"></i>
            <span class="visually-hidden">Quote</span>
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm icon-only-btn" data-rich-editor-command="createLink" aria-label="Insert link" title="Insert link">
            <i class="bi bi-link-45deg" aria-hidden="true"></i>
            <span class="visually-hidden">Insert link</span>
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm icon-only-btn" data-rich-editor-command="removeFormat" aria-label="Clear formatting" title="Clear formatting">
            <i class="bi bi-eraser" aria-hidden="true"></i>
            <span class="visually-hidden">Clear formatting</span>
        </button>
    </div>

    <div
        id="{{ $inputId }}_editor"
        class="rich-text-surface form-control {{ $hasError ? 'is-invalid' : '' }}"
        data-rich-editor-editor
        data-rich-editor-target="{{ $inputId }}"
        contenteditable="true"
        role="textbox"
        aria-multiline="true"
        data-placeholder="{{ $placeholder }}"
        style="min-height: {{ $rows * 1.55 }}rem;"
    >{!! $oldValue !!}</div>

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
