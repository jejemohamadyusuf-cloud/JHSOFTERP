<div class="list-action-bar" data-list-actions-panel aria-live="polite">
    @foreach ($staticActions ?? [] as $action)
        <button class="button {{ $action['class'] ?? 'secondary' }}" type="button" data-list-static-action
            data-href="{{ $action['url'] }}">{{ $action['label'] }}</button>
    @endforeach
    <span class="list-action-hint">Pilih satu baris untuk mengaktifkan aksi.</span>
</div>
