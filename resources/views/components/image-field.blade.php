@props([
    'name',
    'id' => null,
    'label' => null,
    'value' => null,
    'preset' => null,
    'accept' => 'image/png,image/jpeg,image/webp,image/gif,image/x-icon,.ico',
    'help' => null,
    'preserve' => true,
    'currentName' => null,
    'aspect' => 'square', // square|wide
])

@php
    $currentUrl = mca_upload_url(is_string($value) ? $value : null);
    $inputId = $id ?: 'mca-upload-'.md5($name);
    $currentField = $currentName ?? ($name.'_current');
    $acceptHint = collect(explode(',', (string) $accept))
        ->map(fn ($part) => strtoupper(ltrim(strrchr(trim($part), '/') ?: trim($part), '.')))
        ->filter()
        ->unique()
        ->take(4)
        ->implode(' · ');
@endphp

@once
    <link rel="stylesheet" href="{{ asset('vendor/mca-upload/mca-upload.css') }}">
@endonce

<div
    class="mca-upload-field"
    x-data="{
        preview: @js($currentUrl),
        dragging: false,
        pick() {
            this.$refs.input.click();
        },
        onChange(event) {
            const file = event.target.files?.[0];
            if (!file) {
                this.preview = @js($currentUrl);
                return;
            }
            this.preview = URL.createObjectURL(file);
        },
        onDrop(event) {
            this.dragging = false;
            const file = event.dataTransfer?.files?.[0];
            if (!file) return;
            const dt = new DataTransfer();
            dt.items.add(file);
            this.$refs.input.files = dt.files;
            this.preview = URL.createObjectURL(file);
        }
    }"
>
    @if ($label)
        <label class="mca-perm-label mca-upload-field__label" for="{{ $inputId }}">{{ $label }}</label>
    @endif

    @if ($help)
        <p class="mca-perm-help">{{ $help }}</p>
    @endif

    <div
        class="mca-upload-tile mca-upload-tile--{{ $aspect }}"
        :class="{
            'mca-upload-tile--filled': preview,
            'mca-upload-tile--dragging': dragging
        }"
        role="button"
        tabindex="0"
        @click="pick()"
        @keydown.enter.prevent="pick()"
        @keydown.space.prevent="pick()"
        @dragenter.prevent="dragging = true"
        @dragover.prevent="dragging = true"
        @dragleave.prevent="dragging = false"
        @drop.prevent="onDrop($event)"
        aria-label="{{ $label ?: __('mca-upload::upload.field.pick') }}"
    >
        <input
            id="{{ $inputId }}"
            x-ref="input"
            type="file"
            name="{{ $name }}"
            accept="{{ $accept }}"
            class="mca-upload-tile__input"
            @change="onChange($event)"
            @click.stop
            @if ($preset) data-mca-upload-preset="{{ $preset }}" @endif
        >

        <template x-if="preview">
            <img class="mca-upload-tile__preview" :src="preview" alt="">
        </template>

        <div class="mca-upload-tile__empty" x-show="!preview" x-cloak>
            <span class="mca-upload-tile__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                </svg>
            </span>
            <span class="mca-upload-tile__title">{{ __('mca-upload::upload.field.pick') }}</span>
            <span class="mca-upload-tile__hint">{{ $acceptHint !== '' ? $acceptHint : __('mca-upload::upload.field.formats') }}</span>
        </div>

        <div class="mca-upload-tile__overlay" x-show="preview" x-cloak>
            <span class="mca-upload-tile__chip">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 7.125L16.875 4.5"/>
                </svg>
                {{ __('mca-upload::upload.field.change') }}
            </span>
        </div>
    </div>

    @if ($preserve && is_string($value) && $value !== '')
        <input type="hidden" name="{{ $currentField }}" value="{{ $value }}">
    @endif
</div>
