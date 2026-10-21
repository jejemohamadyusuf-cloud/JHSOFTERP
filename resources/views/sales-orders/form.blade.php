@extends('layouts.app', ['title' => $order ? 'Ubah Sales Order' : 'Buat Sales Order'])

@section('content')
    <div class="section-heading">
        <div><h1>{{ $order ? 'Ubah Sales Order' : 'Buat Sales Order' }}</h1><p>Dokumen dibuat sebagai draft; konfirmasi tidak mengurangi stok.</p></div>
        <a class="button secondary" href="{{ $order ? route('sales-orders.show', $order) : route('sales-orders.index') }}">← Kembali</a>
    </div>
    <x-pic-guide module="sales-orders" />
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
            <div><h2>Barang pesanan</h2><p>Harga otomatis mengikuti Master Harga berdasarkan tanggal pesanan dan tidak dapat diubah di Sales Order.</p></div>
            <button class="button secondary" type="button" id="add-sales-line">＋ Tambah barang</button>
        </div>
        @error('lines')<div class="field-error line-error">{{ $message }}</div>@enderror
        <div class="sales-lines" id="sales-lines">
            @php($lines = old('lines', $order?->lines->map(fn ($line) => ['item_id' => $line->item_id, 'qty' => number_format((float) $line->qty, 2, '.', '')])->all() ?? [['item_id' => '', 'qty' => '']]))
            @foreach ($lines as $index => $line)
                @include('sales-orders._line', ['index' => $index, 'line' => $line, 'items' => $items])
            @endforeach
        </div>
        <template id="sales-line-template">
            @include('sales-orders._line', ['index' => '__INDEX__', 'line' => ['item_id' => '', 'qty' => ''], 'items' => $items])
        </template>
        <div class="form-actions"><a class="button secondary" href="{{ $order ? route('sales-orders.show', $order) : route('sales-orders.index') }}">Batal</a><button class="button primary" type="submit">Simpan sebagai draft</button></div>
    </form>
    <script>
        (() => {
            const container = document.getElementById('sales-lines');
            const template = document.getElementById('sales-line-template').innerHTML;
            const orderDate = document.getElementById('order_date');
            const lookupUrl = @json(route('sales-orders.price'));

            const updatePrice = async (line) => {
                const itemId = line.querySelector('[data-item-select]').value;
                const preview = line.querySelector('[data-price-preview]');
                preview.textContent = 'Memuat harga...';
                preview.dataset.ready = 'false';

                if (!itemId || !orderDate.value) {
                    preview.textContent = 'Pilih barang dan tanggal pesanan';
                    return;
                }

                const url = new URL(lookupUrl, window.location.origin);
                url.searchParams.set('item_id', itemId);
                url.searchParams.set('order_date', orderDate.value);

                try {
                    const response = await fetch(url, { headers: { Accept: 'application/json' } });
                    const result = await response.json();

                    if (!response.ok) {
                        preview.textContent = result.message || 'Harga tidak ditemukan';
                        return;
                    }

                    preview.textContent = `Rp ${Number(result.price).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 4 })}`;
                    preview.dataset.ready = 'true';
                } catch {
                    preview.textContent = 'Harga gagal dimuat. Coba lagi.';
                }
            };

            const refreshPrices = () => container.querySelectorAll('.sales-line').forEach(updatePrice);

            container.addEventListener('change', (event) => {
                if (event.target.matches('[data-item-select]')) {
                    updatePrice(event.target.closest('.sales-line'));
                }
            });
            orderDate.addEventListener('change', refreshPrices);
            document.getElementById('add-sales-line').addEventListener('click', () => {
                const index = container.querySelectorAll('.sales-line').length;
                container.insertAdjacentHTML('beforeend', template.replaceAll('__INDEX__', index));
                updatePrice(container.lastElementChild);
            });
            container.addEventListener('click', (event) => {
                if (event.target.closest('.remove-line')) {
                    event.target.closest('.sales-line').remove();
                }
            });
            document.querySelector('.form-card').addEventListener('submit', (event) => {
                if ([...container.querySelectorAll('.sales-line')].some((line) => line.querySelector('[data-price-preview]').dataset.ready !== 'true')) {
                    event.preventDefault();
                    window.alert('Pastikan semua barang memiliki Master Harga yang berlaku pada tanggal pesanan.');
                }
            });
            refreshPrices();
        })();
    </script>
@endsection
