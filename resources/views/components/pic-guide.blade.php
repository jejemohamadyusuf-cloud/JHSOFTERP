@props(['module'])

@php($guide = \App\Models\PicGuide::query()->where('module', $module)->where('is_enabled', true)->first())

@if ($guide)
    <div class="note-panel" aria-label="Panduan PIC">
        <span class="note-icon">i</span>
        <span>
            <strong>PIC umum (acuan belajar)</strong><br>
            Divisi: {{ $guide->department }}<br>
            Penginput: {{ $guide->input_role }}<br>
            Pemeriksa/penanggung jawab: {{ $guide->review_role }}<br>
            <span class="muted">Panduan ini bersifat informatif dan belum menetapkan user, hak akses, atau approval.</span>
        </span>
    </div>
@endif
