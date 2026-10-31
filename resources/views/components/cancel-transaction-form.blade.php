<form method="POST" action="{{ route('transactions.cancel', ['type' => $type, 'id' => $id]) }}"
    data-cancel-transaction>
    @csrf
    <input type="hidden" name="cancellation_reason" value="">
    <button class="button danger" type="submit">Batalkan</button>
</form>
