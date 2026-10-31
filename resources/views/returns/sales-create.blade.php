@extends('layouts.app', ['title' => 'Buat Retur Penjualan'])

@section('content')
    <div class="section-heading">
        <div><h1>Buat Retur Penjualan</h1><p>Pilih barang yang dikembalikan pelanggan dari delivery.</p></div>
        <a class="button secondary" href="{{ route('sales-returns.index') }}">← Riwayat Retur</a>
    </div>
    <form class="form-card" method="GET" action="{{ route('sales-returns.create') }}">
        <div class="form-grid">
            <div class="form-field">
                <label for="source_id">Delivery <span class="required">*</span></label>
                <select id="source_id" name="source_id" required>
                    <option value="">Pilih delivery</option>
                    @foreach ($sources as $delivery)
                        <option value="{{ $delivery->id }}" @selected((int) request('source_id') === $delivery->id)>
                            {{ $delivery->delivery_number }} — {{ $delivery->salesOrder->customer->name }}
                        </option>
                    @endforeach
                </select>
                @if ($sources->isEmpty())<span class="muted">Belum ada delivery untuk diretur.</span>@endif
            </div>
            <div class="form-field form-actions"><button class="button secondary" type="submit">Muat Delivery</button></div>
        </div>
    </form>

    @if ($source)
        <div class="note-panel"><span class="note-icon">i</span><span>Delivery {{ $source->delivery_number }} · {{ $source->salesOrder->customer->name }} · Gudang {{ $source->warehouse->name }}. Barang yang diterima kembali masuk ke gudang/lokasi asal.</span></div>
        <form class="form-card" method="POST" action="{{ route('sales-returns.store') }}">
            @csrf
            <input type="hidden" name="source_id" value="{{ $source->id }}">
            <div class="form-grid">
                <div class="form-field">
                    <label for="return_date">Tanggal retur <span class="required">*</span></label>
                    <input id="return_date" name="return_date" type="date" value="{{ old('return_date', now()->toDateString()) }}" required>
                    @error('return_date')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-field">
                    <label for="reason">Alasan retur <span class="required">*</span></label>
                    <input id="reason" name="reason" maxlength="100" value="{{ old('reason') }}" placeholder="Contoh: barang rusak / salah kirim" required>
                    @error('reason')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-field full-width">
                    <label for="notes">Catatan</label>
                    <textarea id="notes" name="notes" rows="2" maxlength="2000">{{ old('notes') }}</textarea>
                    @error('notes')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>
            @error('source_id')<div class="flash error">{{ $message }}</div>@enderror
            @error('lines')<div class="flash error">{{ $message }}</div>@enderror
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Barang delivery</th><th>Qty dikirim</th><th>Sudah diretur</th><th>Sisa dapat diretur</th><th>Qty retur</th></tr></thead>
                    <tbody>
                        @php($lineIndex = 0)
                        @foreach ($source->lines as $line)
                            @php($returned = (float) $line->salesReturnLines->sum('qty'))
                            @php($remaining = max(0, (float) $line->qty - $returned))
                            @if ($remaining > 0)
                                <tr>
                                    <td><strong>{{ $line->item->code }}</strong><br><span class="muted">{{ $line->item->name }}</span></td>
                                    <td>{{ number_format((float) $line->qty, 2, ',', '.') }} {{ $line->item->unit->code }}</td>
                                    <td>{{ number_format($returned, 2, ',', '.') }}</td>
                                    <td>{{ number_format($remaining, 2, ',', '.') }}</td>
                                    <td>
                                        <input name="lines[{{ $lineIndex }}][source_line_id]" type="hidden" value="{{ $line->id }}">
                                        <input name="lines[{{ $lineIndex }}][qty]" type="number" min="0" max="{{ number_format($remaining, 2, '.', '') }}" step="0.01" value="{{ old("lines.$lineIndex.qty", '0') }}">
                                        @error("lines.$lineIndex.qty")<span class="field-error">{{ $message }}</span>@enderror
                                    </td>
                                </tr>
                                @php($lineIndex++)
                            @endif
                        @endforeach
                        @if ($lineIndex === 0)<tr><td class="empty-state" colspan="5">Semua barang pada delivery ini sudah diretur.</td></tr>@endif
                    </tbody>
                </table>
            </div>
            @if ($lineIndex > 0)
                <div class="form-actions"><button class="button primary" type="submit">Simpan Retur Penjualan</button></div>
            @endif
        </form>
    @elseif (request()->filled('source_id'))
        <div class="note-panel"><span class="note-icon">i</span><span>Delivery tidak tersedia atau tidak dapat dipilih.</span></div>
    @endif
@endsection
