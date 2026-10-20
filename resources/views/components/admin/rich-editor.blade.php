@props(['name', 'label', 'value' => null, 'hint' => null, 'required' => false, 'placeholder' => 'Start writing…', 'compact' => false])
@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $error = $errors->first($key);
    $id = 'f-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name);
@endphp
<div {{ $attributes->only('class') }}>
    <p class="a-label" id="{{ $id }}-label">{{ $label }} @if ($required)<span class="text-lime" aria-hidden="true">*</span>@endif</p>
    <div class="a-editor" x-data="richEditor({ placeholder: @js($placeholder) })" data-label="{{ $label }}" @if ($name === 'body') @restore-body.window="setContent($event.detail.html)" @endif>
        <div class="a-editor-toolbar" role="toolbar" aria-label="Formatting" aria-controls="{{ $id }}-content">
            <label class="sr-only" for="{{ $id }}-block">Text style</label>
            <select id="{{ $id }}-block" class="a-input !min-h-8 !w-auto !py-1 text-xs" @change="setHeading(Number($event.target.value))" :value="currentBlock()">
                <option value="0">Paragraph</option>
                <option value="2">Heading 2</option>
                <option value="3">Heading 3</option>
                <option value="4">Heading 4</option>
            </select>
            <span class="sep"></span>
            <button type="button" @click="run('toggleBold')" :aria-pressed="isActive('bold')" aria-label="Bold" title="Bold"><strong>B</strong></button>
            <button type="button" @click="run('toggleItalic')" :aria-pressed="isActive('italic')" aria-label="Italic" title="Italic"><em>I</em></button>
            <button type="button" @click="run('toggleUnderline')" :aria-pressed="isActive('underline')" aria-label="Underline" title="Underline"><span class="underline">U</span></button>
            <button type="button" @click="run('toggleStrike')" :aria-pressed="isActive('strike')" aria-label="Strikethrough" title="Strikethrough"><span class="line-through">S</span></button>
            <button type="button" @click="link()" :aria-pressed="isActive('link')" aria-label="Link" title="Link"><x-icon name="link" :size="16" /></button>
            <span class="sep"></span>
            <button type="button" @click="run('toggleBulletList')" :aria-pressed="isActive('bulletList')" aria-label="Bulleted list" title="Bulleted list">• List</button>
            <button type="button" @click="run('toggleOrderedList')" :aria-pressed="isActive('orderedList')" aria-label="Numbered list" title="Numbered list">1. List</button>
            <button type="button" @click="run('toggleBlockquote')" :aria-pressed="isActive('blockquote')" aria-label="Quote" title="Quote"><x-icon name="quote" :size="16" /></button>
            @unless ($compact)
                <button type="button" @click="run('toggleCode')" :aria-pressed="isActive('code')" aria-label="Inline code" title="Inline code"><span class="font-mono">&lt;/&gt;</span></button>
                <button type="button" @click="run('toggleCodeBlock')" :aria-pressed="isActive('codeBlock')" aria-label="Code block" title="Code block"><span class="font-mono">{ }</span></button>
                <button type="button" @click="run('setHorizontalRule')" aria-label="Divider" title="Divider"><x-icon name="minus" :size="16" /></button>
                <span class="sep"></span>
                @can('media.view')
                    <button type="button" @click="image()" aria-label="Insert image" title="Insert image"><x-icon name="image" :size="16" /></button>
                @endcan
                <button type="button" @click="video()" aria-label="Embed video" title="Embed YouTube or Vimeo video"><x-icon name="play" :size="16" /></button>
            @endunless
            <span class="sep"></span>
            <button type="button" @click="run('undo')" aria-label="Undo" title="Undo">↶</button>
            <button type="button" @click="run('redo')" aria-label="Redo" title="Redo">↷</button>
            <span class="ml-auto self-center px-2 text-xs a-subtle" aria-live="polite"><span x-text="wordCount"></span> words</span>
        </div>
        <div class="a-editor-content" id="{{ $id }}-content" x-ref="content" aria-labelledby="{{ $id }}-label"></div>
        <textarea x-ref="input" name="{{ $name }}" class="hidden" aria-hidden="true" tabindex="-1">{{ old($key, $value) }}</textarea>
    </div>
    @if ($hint)
        <p class="a-hint">{{ $hint }}</p>
    @endif
    @if ($error)
        <p class="a-error">{{ $error }}</p>
    @endif
</div>
