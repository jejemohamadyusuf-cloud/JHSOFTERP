<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\BomRequest;
use App\Models\Bom;
use App\Models\Item;
use App\Models\Unit;
use App\Services\BomService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

final class BomController extends Controller
{
    public function __construct(private readonly BomService $boms)
    {
    }

    public function index(): View
    {
        return view('boms.index', ['boms' => $this->boms->paginate()]);
    }

    public function create(): View
    {
        return $this->form(null);
    }

    public function store(BomRequest $request): RedirectResponse
    {
        $bom = $this->boms->save($request->validated());

        return Redirect::route('boms.edit', $bom)->with('status', 'BOM berhasil ditambahkan.');
    }

    public function edit(int $bom): View
    {
        return $this->form($this->boms->find($bom));
    }

    public function update(BomRequest $request, int $bom): RedirectResponse
    {
        $this->boms->save($request->validated(), $bom);

        return Redirect::route('boms.edit', $bom)->with('status', 'BOM berhasil diperbarui.');
    }

    public function destroy(int $bom): RedirectResponse
    {
        try {
            $this->boms->delete($bom);
        } catch (QueryException) {
            return Redirect::route('boms.index')
                ->withErrors(['delete' => 'BOM tidak dapat dihapus karena sudah digunakan oleh data lain.']);
        }

        return Redirect::route('boms.index')->with('status', 'BOM berhasil dihapus.');
    }

    private function form(?Bom $bom): View
    {
        return view('boms.form', [
            'bom' => $bom,
            'items' => Item::query()->orderBy('name')->get(),
            'units' => Unit::query()->orderBy('name')->get(),
        ]);
    }
}
