@props([
    'name',
    'label' => null,
    'value' => null,
    'preset' => null,
    'accept' => 'image/png,image/jpeg,image/webp,image/gif,image/x-icon,.ico',
    'help' => null,
])

@php
    $currentUrl = mca_upload_url(is_string($value) ? $value : null);
    $inputId = 'mca-upload-'.md5($name);
@endphp

<div
    class="mca-upload-field"
    x-data="{
        preview: @js($currentUrl),
        onChange(event) {
            const file = event.target.files?.[0];
            if (!file) {
                this.preview = @js($currentUrl);
                return;
            }
            this.preview = URL.createObjectURL(file);
        }
    }"
>
    @if ($label)
        <label class="mca-perm-label" for="{{ $inputId }}">{{ $label }}</label>
    @endif

    @if ($help)
        <p class="mca-perm-help">{{ $help }}</p>
    @endif

    <div class="mca-upload-field__row" style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;">
        <template x-if="preview">
            <img :src="preview" alt="" style="height:3rem;max-width:10rem;object-fit:contain;border:1px solid var(--mca-ui-border, #e2e8f0);border-radius:0.5rem;padding:0.25rem;background:#fff;">
        </template>

        <input
            id="{{ $inputId }}"
            type="file"
            name="{{ $name }}"
            accept="{{ $accept }}"
            class="mca-perm-input"
            @change="onChange($event)"
            @if ($preset) data-mca-upload-preset="{{ $preset }}" @endif
        >

        @if (is_string($value) && $value !== '')
            <input type="hidden" name="{{ $name }}_current" value="{{ $value }}">
        @endif
    </div>
</div>
