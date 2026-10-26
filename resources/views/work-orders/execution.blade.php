<div class="section-heading"><div><h2>Material Produksi</h2><p>Pengeluaran material mengurangi stok sesuai gudang dan lokasi sumber.</p></div></div>
<div class="table-card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Material</th><th>Kebutuhan</th><th>Sudah dikeluarkan</th><th>Sisa</th><th>Pengeluaran</th></tr></thead>
            <tbody>
                @forelse ($workOrder->materials as $material)
                    @php($remaining = max(0, (float) $material->qty_required - (float) $material->qty_issued))
                    <tr>
                        <td><strong>{{ $material->item->code }}</strong><br><span class="muted">{{ $material->item->name }}</span></td>
                        <td>{{ number_format((float) $material->qty_required, 2, ',', '.') }} {{ $material->item->unit->code }}</td>
                        <td>{{ number_format((float) $material->qty_issued, 2, ',', '.') }} {{ $material->item->unit->code }}</td>
                        <td>{{ number_format($remaining, 2, ',', '.') }} {{ $material->item->unit->code }}</td>
                        <td>
                            @if ($remaining > 0 && ! in_array($workOrder->status, ['completed', 'cancelled'], true))
                                <form class="actions" method="POST" action="{{ route('work-orders.materials.issue', [$workOrder, $material]) }}">
                                    @csrf
                                    <input aria-label="Kuantitas material" name="qty" type="number" min="0.01" max="{{ number_format($remaining, 2, '.', '') }}" step="0.01" value="{{ number_format($remaining, 2, '.', '') }}" required>
                                    <input type="hidden" name="issue_date" value="{{ now()->toDateString() }}">
                                    <select aria-label="Lokasi sumber material" name="location_id">
                                        <option value="">Tanpa lokasi</option>
                                        @foreach ($locations as $location)
                                            <option value="{{ $location->id }}" @selected($material->location_id === $location->id)>{{ $location->code }} — {{ $location->name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="button small primary" type="submit">Keluarkan</button>
                                </form>
                            @else
                                <span class="muted">Kebutuhan terpenuhi</span>
                            @endif
                            @error('qty')<span class="field-error">{{ $message }}</span>@enderror
                            @error('location_id')<span class="field-error">{{ $message }}</span>@enderror
                        </td>
                    </tr>
                @empty
                    <tr><td class="empty-state" colspan="5">BOM Work Order ini tidak memiliki material.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="section-heading"><div><h2>Hasil Produksi</h2><p>Tercatat {{ number_format((float) $workOrder->qty_produced, 2, ',', '.') }} dari {{ number_format((float) $workOrder->qty_planned, 2, ',', '.') }} {{ $workOrder->item->unit->code }}.</p></div></div>
@if (! in_array($workOrder->status, ['completed', 'cancelled'], true) && ! $workOrder->materials->contains(fn ($material) => (float) $material->qty_issued + 0.0000001 < (float) $material->qty_required))
    <form class="form-card" method="POST" action="{{ route('work-orders.production-entries.store', $workOrder) }}">
        @csrf
        <div class="form-grid">
            <div class="form-field"><label for="production_date">Tanggal produksi</label><input id="production_date" name="production_date" type="date" value="{{ now()->toDateString() }}" required></div>
            <div class="form-field"><label for="production_qty">Kuantitas produksi</label><input id="production_qty" name="qty" type="number" min="0.01" step="0.01" required></div>
            <div class="form-field"><label for="production_cost">Biaya per unit</label><input id="production_cost" name="cost" type="number" min="0" step="0.01" value="0" required></div>
            <div class="form-field"><label for="production_notes">Catatan</label><input id="production_notes" name="notes" type="text" maxlength="2000"></div>
        </div>
        @error('qty')<span class="field-error">{{ $message }}</span>@enderror
        <div class="form-actions"><button class="button primary" type="submit">Catat hasil produksi</button></div>
    </form>
@else
    <div class="note-panel"><span class="note-icon">i</span><span>Produksi dapat dicatat setelah semua material dikeluarkan dan Work Order belum selesai atau dibatalkan.</span></div>
@endif

<div class="section-heading"><div><h2>Pemeriksaan QC dan Penerimaan</h2><p>Hanya kuantitas yang lulus QC yang dapat diterima sebagai stok barang jadi.</p></div></div>
@forelse ($workOrder->productionEntries as $entry)
    <div class="table-card" style="margin-bottom: 14px">
        <div class="table-toolbar">
            <strong>{{ $entry->production_date->format('d/m/Y') }} · {{ number_format((float) $entry->qty, 2, ',', '.') }} {{ $workOrder->item->unit->code }}</strong>
            <span>Biaya/unit {{ number_format((float) $entry->cost, 2, ',', '.') }}</span>
        </div>
        @php($inspected = $entry->qcInspections->sum(fn ($inspection) => (float) $inspection->qty_pass + (float) $inspection->qty_reject))
        @if ($inspected + 0.0000001 < (float) $entry->qty)
            <form class="form-card" method="POST" action="{{ route('work-orders.inspections.store', [$workOrder, $entry]) }}">
                @csrf
                <div class="form-grid">
                    <div class="form-field"><label for="inspection_date_{{ $entry->id }}">Tanggal QC</label><input id="inspection_date_{{ $entry->id }}" name="inspection_date" type="date" value="{{ now()->toDateString() }}" required></div>
                    <div class="form-field"><label for="qty_pass_{{ $entry->id }}">Lulus</label><input id="qty_pass_{{ $entry->id }}" name="qty_pass" type="number" min="0" step="0.01" value="0" required></div>
                    <div class="form-field"><label for="qty_reject_{{ $entry->id }}">Ditolak</label><input id="qty_reject_{{ $entry->id }}" name="qty_reject" type="number" min="0" step="0.01" value="0" required></div>
                </div>
                <input name="notes" type="text" maxlength="2000" placeholder="Catatan pemeriksaan">
                @error('qty_pass')<span class="field-error">{{ $message }}</span>@enderror
                <div class="form-actions"><button class="button primary" type="submit">Simpan hasil QC</button></div>
            </form>
        @endif
        @foreach ($entry->qcInspections as $inspection)
            @php($receiptRemaining = max(0, (float) $inspection->qty_pass - (float) $inspection->finishedGoodsReceipts->sum('qty')))
            <div class="table-toolbar">
                <span>QC {{ $inspection->inspection_date->format('d/m/Y') }} · Lulus {{ number_format((float) $inspection->qty_pass, 2, ',', '.') }} · Ditolak {{ number_format((float) $inspection->qty_reject, 2, ',', '.') }}</span>
                @if ($receiptRemaining > 0)
                    <form class="actions" method="POST" action="{{ route('work-orders.receipts.store', [$workOrder, $inspection]) }}">
                        @csrf
                        <input type="hidden" name="receipt_date" value="{{ now()->toDateString() }}">
                        <input aria-label="Kuantitas penerimaan barang jadi" name="qty" type="number" min="0.01" max="{{ number_format($receiptRemaining, 2, '.', '') }}" step="0.01" value="{{ number_format($receiptRemaining, 2, '.', '') }}" required>
                        <select aria-label="Lokasi barang jadi" name="location_id">
                            <option value="">Tanpa lokasi</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->code }} — {{ $location->name }}</option>
                            @endforeach
                        </select>
                        <button class="button small primary" type="submit">Terima stok</button>
                    </form>
                @else
                    <span>Semua barang lulus telah diterima</span>
                @endif
            </div>
        @endforeach
    </div>
@empty
    <div class="note-panel"><span class="note-icon">i</span><span>Belum ada catatan produksi untuk diperiksa.</span></div>
@endforelse
