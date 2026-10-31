<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\MasterDataRequest;
use App\Models\Unit;
use App\Services\MasterDataService;
use App\Support\MasterDataCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

final class MasterDataController extends Controller
{
    public function __construct(private readonly MasterDataService $masterData) {}

    public function dashboard(): View
    {
        return view('master-data.dashboard', [
            'masters' => MasterDataCatalog::all(),
        ]);
    }

    public function index(string $master): View
    {
        $this->authorizeMaster($master);

        return view('master-data.index', [
            'masterKey' => $master,
            'definition' => $this->masterData->definition($master),
            'records' => $this->masterData->paginate($master),
        ]);
    }

    public function create(string $master): View
    {
        $this->authorizeMaster($master);

        return view('master-data.form', [
            'masterKey' => $master,
            'definition' => $this->masterData->definition($master),
            'record' => null,
            'options' => $this->options($master),
            'conversionUnits' => $master === 'items' ? Unit::query()->orderBy('name')->get() : collect(),
        ]);
    }

    public function store(MasterDataRequest $request, string $master): RedirectResponse
    {
        $this->authorizeMaster($master);
        $this->masterData->create($master, $request->validated());

        return Redirect::route('master-data.index', $master)->with('status', 'Data berhasil ditambahkan.');
    }

    public function edit(string $master, int $record): View
    {
        $this->authorizeMaster($master);

        return view('master-data.form', [
            'masterKey' => $master,
            'definition' => $this->masterData->definition($master),
            'record' => $this->masterData->find($master, $record),
            'options' => $this->options($master),
            'conversionUnits' => $master === 'items' ? Unit::query()->orderBy('name')->get() : collect(),
        ]);
    }

    public function update(MasterDataRequest $request, string $master, int $record): RedirectResponse
    {
        $this->authorizeMaster($master);
        $this->masterData->update($master, $record, $request->validated());

        return Redirect::route('master-data.index', $master)->with('status', 'Data berhasil diperbarui.');
    }

    public function destroy(string $master, int $record): RedirectResponse
    {
        $this->authorizeMaster($master);

        try {
            $this->masterData->delete($master, $record);
        } catch (QueryException) {
            return Redirect::route('master-data.index', $master)
                ->withErrors(['delete' => 'Data tidak dapat dihapus karena sudah digunakan oleh transaksi atau master lain.']);
        }

        return Redirect::route('master-data.index', $master)->with('status', 'Data berhasil dihapus.');
    }

    /**
     * @return array<string, Collection<int, Model>>
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
            $query = $model::query()->orderBy('name');

            if ($master === 'chart-of-accounts' && $field === 'parent_id') {
                $query->where('is_header', true);
                $recordId = request()->route('record');
                if ($recordId) {
                    $query->where('id', '!=', $recordId);
                }
            }

            $options[$field] = $query->get();
        }

        return $options;
    }

    private function authorizeMaster(string $master): void
    {
        $user = auth()->user();
        $explicitMasterDataAccess = $user !== null
            && isset($user->permissions)
            && in_array('master-data', $user->permissions, true);
        $allowed = $master === 'chart-of-accounts'
            ? $user?->canAccess('master-data') === true && $user->canAccess('finance')
            : $user?->canAccess('master-data') === true
                && ($user->hasRole('sales', 'inventory') || $explicitMasterDataAccess);

        abort_unless($allowed, 403);
    }
}
