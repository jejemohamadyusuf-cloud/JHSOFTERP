@extends('layouts.app', ['title' => $price ? 'Ubah Periode Harga' : 'Tambah Harga'])

@section('content')
    <div class="section-heading">
        <div><h1>{{ $price ? 'Ubah Periode Harga' : 'Tambah Harga' }}</h1><p>Harga jual digunakan Sales Order; harga beli digunakan Purchase Order.</p></div>
        <a class="button secondary" href="{{ route('master-prices.index') }}">← Kembali</a>
    </div>
    <x-pic-guide module="master-prices" />
    <form class="form-card" method="POST" action="{{ $price ? route('master-prices.update', $price) : route('master-prices.store') }}">
        @csrf
        @if ($price) @method('PUT') @endif
        <div class="form-grid">
            <div class="form-field">
                <label for="item_id">Barang <span class="required">*</span></label>
                <select id="item_id" name="item_id" required>
                    <option value="">Pilih barang</option>
                    @foreach ($items as $item)
                        <option value="{{ $item->id }}" @selected((string) old('item_id', $price?->item_id) === (string) $item->id)>{{ $item->code }} — {{ $item->name }} ({{ ['raw' => 'Bahan baku', 'semi' => 'Setengah jadi', 'finished' => 'Barang jadi'][$item->item_type] ?? $item->item_type }})</option>
                    @endforeach
                </select>
                @error('item_id')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-field">
                <label for="price_type">Jenis harga <span class="required">*</span></label>
                <select id="price_type" name="price_type" required>
                    <option value="sell" @selected(old('price_type', $price?->price_type ?? 'sell') === 'sell')>Harga jual — Sales Order</option>
                    <option value="buy" @selected(old('price_type', $price?->price_type) === 'buy')>Harga beli — Purchase Order</option>
                </select>
                @error('price_type')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-field">
                <label for="price">Harga per unit (Rp) <span class="required">*</span></label>
                <input id="price" name="price" type="number" min="0" step="0.0001" value="{{ old('price', $price?->price) }}" required>
                @error('price')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-field">
                <label for="start_date">Mulai berlaku <span class="required">*</span></label>
                <input id="start_date" name="start_date" type="date" value="{{ old('start_date', $price?->start_date->format('Y-m-d')) }}" required>
                @error('start_date')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-field">
                <label for="end_date">Akhir berlaku</label>
                <input id="end_date" name="end_date" type="date" value="{{ old('end_date', $price?->end_date?->format('Y-m-d')) }}">
                @error('end_date')<span class="field-error">{{ $message }}</span>@enderror
                <span class="muted">Kosongkan jika harga berlaku tanpa batas akhir.</span>
            </div>
        </div>
        @if ($price && ($price->salesOrderLines()->exists() || $price->purchaseOrderLines()->exists()))
            <div class="note-panel"><span class="note-icon">i</span><span>Harga ini sudah dipakai transaksi. Nilai, jenis harga, barang, dan tanggal mulai dikunci; Anda hanya dapat menutup atau menyesuaikan tanggal akhirnya.</span></div>
        @else
            <div class="note-panel"><span class="note-icon">i</span><span>Sebelum membuat harga baru, tutup periode harga lama pada tanggal sebelum harga baru mulai. Sistem menolak periode harga yang tumpang tindih.</span></div>
        @endif
        <div class="form-actions"><a class="button secondary" href="{{ route('master-prices.index') }}">Batal</a><button class="button primary" type="submit">Simpan harga</button></div>
    </form>
@endsection
