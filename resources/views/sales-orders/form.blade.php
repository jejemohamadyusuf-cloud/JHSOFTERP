@extends('layouts.app', ['title' => $order ? 'Ubah Sales Order' : 'Buat Sales Order'])

@section('content')
    <div class="section-heading">
        <div><h1>{{ $order ? 'Ubah Sales Order' : 'Buat Sales Order' }}</h1><p>Dokumen dibuat sebagai draft; konfirmasi tidak mengurangi stok.</p></div>
        <a class="button secondary" href="{{ $order ? route('sales-orders.show', $order) : route('sales-orders.index') }}">← Kembali</a>
    </div>
    <form class="form-card" method="POST" action="{{ $order ? route('sales-orders.update', $order) : route('sales-orders.store') }}">
        @csrf
        @if ($order) @method('PUT') @endif
        <div class="form-grid">
            <div class="form-field">
                <label for="customer_id">Pelanggan <span class="required">*</span></label>
                <select id="customer_id" name="customer_id" required>
                    <option value="">Pilih pelanggan</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) old('customer_id', $order?->customer_id) === (string) $customer->id)>{{ $customer->code }} — {{ $customer->name }}</option>
                    @endforeach
                </select>
                @error('customer_id')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-field">
                <label for="order_date">Tanggal pesanan <span class="required">*</span></label>
                <input id="order_date" name="order_date" type="date" value="{{ old('order_date', $order?->order_date->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
                @error('order_date')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-field full-width">
                <label for="notes">Catatan</label>
                <textarea id="notes" name="notes" rows="2" maxlength="5000">{{ old('notes', $order?->notes) }}</textarea>
                @error('notes')<span class="field-error">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="bom-lines-heading">
            <div><h2>Barang pesanan</h2><p>Harga dan kuantitas dicatat per baris; satuan mengikuti master barang.</p></div>
            <button class="button secondary" type="button" id="add-sales-line">＋ Tambah barang</button>
        </div>
        @error('lines')<div class="field-error line-error">{{ $message }}</div>@enderror
        <div class="sales-lines" id="sales-lines">
            @php($lines = old('lines', $order?->lines->map(fn ($line) => ['item_id' => $line->item_id, 'qty' => $line->qty, 'unit_price' => $line->unit_price])->all() ?? [['item_id' => '', 'qty' => '', 'unit_price' => '']]))
            @foreach ($lines as $index => $line)
                @include('sales-orders._line', ['index' => $index, 'line' => $line, 'items' => $items])
            @endforeach
        </div>
        <template id="sales-line-template">
            @include('sales-orders._line', ['index' => '__INDEX__', 'line' => ['item_id' => '', 'qty' => '', 'unit_price' => ''], 'items' => $items])
        </template>
        <div class="form-actions"><a class="button secondary" href="{{ $order ? route('sales-orders.show', $order) : route('sales-orders.index') }}">Batal</a><button class="button primary" type="submit">Simpan sebagai draft</button></div>
    </form>
    <script>
        (() => {
            const container = document.getElementById('sales-lines');
            const template = document.getElementById('sales-line-template').innerHTML;
            document.getElementById('add-sales-line').addEventListener('click', () => {
                const index = container.querySelectorAll('.sales-line').length;
                container.insertAdjacentHTML('beforeend', template.replaceAll('__INDEX__', index));
            });
            container.addEventListener('click', (event) => {
                if (event.target.closest('.remove-line')) {
                    event.target.closest('.sales-line').remove();
                }
            });
        })();
    </script>
@endsection
