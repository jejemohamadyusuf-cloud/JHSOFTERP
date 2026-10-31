<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\TransactionCancellationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

final class TransactionCancellationController extends Controller
{
    private const PERMISSIONS = [
        'sales-order' => 'sales-orders',
        'delivery' => 'deliveries',
        'sales-return' => 'sales-returns',
        'purchase-order' => 'purchasing',
        'receiving' => 'purchasing',
        'purchase-return' => 'purchase-returns',
        'invoice' => 'finance',
        'payment' => 'finance',
        'journal' => 'finance',
        'stock-adjustment' => 'stock',
        'stock-take' => 'stock',
        'work-order' => 'production',
    ];

    public function __invoke(Request $request, string $type, int $id, TransactionCancellationService $cancellations): RedirectResponse
    {
        $permission = self::PERMISSIONS[$type] ?? null;
        abort_if($permission === null, 404);
        abort_unless($request->user()->canAccess($permission), 403);

        $data = $request->validate([
            'cancellation_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $cancellations->cancel($type, $id, $data['cancellation_reason'], (int) $request->user()->id);

        return Redirect::back()->with('status', 'Transaksi berhasil dibatalkan. Stok dan jurnal telah dibalik bila diperlukan.');
    }
}
