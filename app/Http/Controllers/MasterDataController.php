<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\MasterDataRequest;
use App\Services\MasterDataService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Redirect;

final class MasterDataController extends Controller
{
    public function __construct(private readonly MasterDataService $masterData)
    {
    }

    public function dashboard(): View
    {
        return view('master-data.dashboard', [
            'masters' => \App\Support\MasterDataCatalog::all(),
        ]);
    }

    public function index(string $master): View
    {
        return view('master-data.index', [
            'masterKey' => $master,
            'definition' => $this->masterData->definition($master),
            'records' => $this->masterData->paginate($master),
        ]);
    }

    public function create(string $master): View
    {
        return view('master-data.form', [
            'masterKey' => $master,
            'definition' => $this->masterData->definition($master),
            'record' => null,
            'options' => $this->options($master),
        ]);
    }

    public function store(MasterDataRequest $request, string $master): RedirectResponse
    {
        $this->masterData->create($master, $request->validated());

        return Redirect::route('master-data.index', $master)->with('status', 'Data berhasil ditambahkan.');
    }

    public function edit(string $master, int $record): View
    {
        return view('master-data.form', [
            'masterKey' => $master,
            'definition' => $this->masterData->definition($master),
            'record' => $this->masterData->find($master, $record),
            'options' => $this->options($master),
        ]);
    }

    public function update(MasterDataRequest $request, string $master, int $record): RedirectResponse
    {
        $this->masterData->update($master, $record, $request->validated());

        return Redirect::route('master-data.index', $master)->with('status', 'Data berhasil diperbarui.');
    }

    public function destroy(string $master, int $record): RedirectResponse
    {
        try {
            $this->masterData->delete($master, $record);
        } catch (QueryException) {
            return Redirect::route('master-data.index', $master)
                ->withErrors(['delete' => 'Data tidak dapat dihapus karena sudah digunakan oleh transaksi atau master lain.']);
        }

        return Redirect::route('master-data.index', $master)->with('status', 'Data berhasil dihapus.');
    }

    /**
     * @return array<string, \Illuminate\Database\Eloquent\Collection<int, \Illuminate\Database\Eloquent\Model>>
     */
    private function options(string $master): array
    {
        $definition = $this->masterData->definition($master);
        $options = [];

        foreach ($definition['fields'] as $field => $fieldDefinition) {
            if ($fieldDefinition['type'] !== 'relation') {
                continue;
            }

            $model = $fieldDefinition['model'];
            $options[$field] = $model::query()->orderBy('name')->get();
        }

        return $options;
    }
}
