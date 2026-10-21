<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PicGuideRequest;
use App\Models\PicGuide;
use App\Support\PicGuideCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

final class PicGuideController extends Controller
{
    public function index(): View
    {
        return view('pic-guides.index', [
            'modules' => PicGuideCatalog::modules(),
            'guides' => PicGuide::query()->get()->keyBy('module'),
        ]);
    }

    public function update(PicGuideRequest $request): RedirectResponse
    {
        $guides = $request->validated('guides');

        DB::transaction(function () use ($guides): void {
            foreach ($guides as $module => $attributes) {
                PicGuide::query()->where('module', $module)->update([
                    'department' => $attributes['department'],
                    'input_role' => $attributes['input_role'],
                    'review_role' => $attributes['review_role'],
                    'is_enabled' => (bool) $attributes['is_enabled'],
                ]);
            }
        });

        return Redirect::route('pic-guides.index')->with('status', 'Panduan PIC berhasil diperbarui.');
    }
}
