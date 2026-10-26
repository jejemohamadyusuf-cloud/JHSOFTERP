@extends('layouts.app', ['title' => 'Buat Purchase Order'])

@section('content')
    <div class="section-heading">
        <div><h1>Buat Purchase Order</h1><p>PO baru berstatus terbuka. Stok berubah setelah barang diterima.</p></div>
        <a class="button secondary" href="{{ route('purchase-orders.index') }}">← Daftar PO</a>
    </div>
    @if ($suppliers->isEmpty() || $items->isEmpty())
        <div class="note-panel"><span class="note-icon">i</span><span>Supplier aktif dan barang aktif harus tersedia sebelum PO dapat dibuat.</span></div>
    @else
        <div class="note-panel"><span class="note-icon">i</span><span>Daftarkan harga beli item untuk supplier di Master Harga Beli terlebih dahulu. Harga supplier lain akan ditampilkan sebagai pembanding.</span></div>
    @endif
    @error('lines')<div class="flash error">{{ $message }}</div>@enderror
    <form class="form-card" method="POST" action="{{ route('purchase-orders.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-field full-width">
                <label for="supplier_id">Supplier <span class="required">*</span></label>
                <select id="supplier_id" name="supplier_id" required>
                    <option value="">Pilih supplier</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>{{ $supplier->code }} — {{ $supplier->name }}</option>
                    @endforeach
                </select>
                @error('supplier_id')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-field">
                <label for="order_date">Tanggal PO <span class="required">*</span></label>
                <input id="order_date" name="order_date" type="date" value="{{ old('order_date', now()->format('Y-m-d')) }}" required>
                @error('order_date')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-field">
                <label for="expected_date">Estimasi tiba</label>
                <input id="expected_date" name="expected_date" type="date" value="{{ old('expected_date') }}">
                @error('expected_date')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-field">
                <label for="notes">Catatan</label>
                <input id="notes" name="notes" value="{{ old('notes') }}" maxlength="5000">
                @error('notes')<span class="field-error">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="bom-lines-heading">
            <div><h2>Barang yang dipesan</h2><p>Harga supplier terpilih otomatis dimuat pada tanggal PO; harga supplier lain ditampilkan untuk perbandingan.</p></div>
            <button class="button secondary" type="button" id="add-purchase-line">＋ Tambah barang</button>
        </div>
        <div id="purchase-lines">
            @php($lines = old('lines', [['item_id' => '', 'qty' => '']]))
            @foreach ($lines as $index => $line)
                @include('purchase-orders._line', ['index' => $index, 'line' => $line])
            @endforeach
        </div>
        <template id="purchase-line-template">@include('purchase-orders._line', ['index' => '__INDEX__', 'line' => ['item_id' => '', 'qty' => '']])</template>
        <div class="form-actions">
            <a class="button secondary" href="{{ route('purchase-orders.index') }}">Batal</a>
            <button class="button primary" type="submit" @disabled($suppliers->isEmpty() || $items->isEmpty())>Simpan Purchase Order</button>
        </div>
    </form>
    <script>
        (() => {
            const container = document.getElementById('purchase-lines');
            const template = document.getElementById('purchase-line-template').innerHTML;
            const orderDate = document.getElementById('order_date');
            const supplier = document.getElementById('supplier_id');
            const lookupUrl = @json(route('purchase-orders.price'));

            const updatePrice = async (line) => {
                const itemId = line.querySelector('[data-item-select]').value;
                const preview = line.querySelector('[data-price-preview]');
                const comparison = line.querySelector('[data-price-comparison]');
                preview.textContent = 'Memuat harga beli...';
                preview.dataset.ready = 'false';
                comparison.textContent = '';

                if (!itemId || !orderDate.value || !supplier.value) {
                    preview.textContent = 'Pilih supplier, barang, dan tanggal PO';
                    return;
                }

                const url = new URL(lookupUrl, window.location.origin);
                url.searchParams.set('item_id', itemId);
                url.searchParams.set('order_date', orderDate.value);
                url.searchParams.set('supplier_id', supplier.value);

                try {
                    const response = await fetch(url, { headers: { Accept: 'application/json' } });
                    const result = await response.json();
                    if (!response.ok) {
                        preview.textContent = result.message || 'Harga beli tidak ditemukan';
                        if (result.comparisons?.length) {
                            comparison.textContent = `Pembanding: ${result.comparisons.map((quote) => `${quote.supplier} Rp ${Number(quote.price).toLocaleString('id-ID')}`).join(' · ')}`;
                        }
                        return;
                    }

                    preview.textContent = `Harga supplier dipilih: Rp ${Number(result.price).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 4 })}`;
                    comparison.textContent = `Pembanding: ${result.comparisons.map((quote) => `${quote.supplier}${quote.selected ? ' (dipilih)' : ''} Rp ${Number(quote.price).toLocaleString('id-ID')}`).join(' · ')}`;
                    preview.dataset.ready = 'true';
                } catch {
                    preview.textContent = 'Harga beli gagal dimuat. Coba lagi.';
                }
            };

            const refreshPrices = () => container.querySelectorAll('.purchase-line').forEach(updatePrice);
            const filterSupplierItems = () => {
                container.querySelectorAll('[data-item-select]').forEach((select) => {
                    [...select.options].forEach((option) => {
                        if (!option.value) return;
                        const supplierIds = (option.dataset.supplierIds || '').split(',').filter(Boolean);
                        option.hidden = !supplier.value || !supplierIds.includes(supplier.value);
                        option.disabled = option.hidden;
                    });
                    if (select.selectedOptions[0]?.disabled) select.value = '';
                });
                refreshPrices();
            };
            container.addEventListener('change', (event) => {
                if (event.target.matches('[data-item-select]')) updatePrice(event.target.closest('.purchase-line'));
            });
            supplier.addEventListener('change', filterSupplierItems);
            orderDate.addEventListener('change', refreshPrices);
            document.getElementById('add-purchase-line').addEventListener('click', () => {
                const index = container.querySelectorAll('.purchase-line').length;
                container.insertAdjacentHTML('beforeend', template.replaceAll('__INDEX__', index));
                updatePrice(container.lastElementChild);
            });
            container.addEventListener('click', (event) => {
                if (event.target.closest('.remove-line')) event.target.closest('.purchase-line').remove();
            });
            document.querySelector('.form-card').addEventListener('submit', (event) => {
                if ([...container.querySelectorAll('.purchase-line')].some((line) => line.querySelector('[data-price-preview]').dataset.ready !== 'true')) {
                    event.preventDefault();
                    window.alert('Pastikan supplier dipilih dan semua barang memiliki harga beli yang berlaku pada tanggal PO.');
                }
            });
            filterSupplierItems();
        })();
    </script>
@endsection
