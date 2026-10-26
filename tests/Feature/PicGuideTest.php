<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PicGuide;
use App\Support\PicGuideCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class PicGuideTest extends AuthenticatedTestCase
{
    use RefreshDatabase;

    public function test_pic_guide_index_shows_editable_defaults_for_each_module(): void
    {
        $response = $this->get(route('pic-guides.index'));

        $response->assertOk()
            ->assertSee('Pengaturan Panduan PIC')
            ->assertSee('Sales Admin atau Sales Representative')
            ->assertSee('PPIC Manager atau Production Manager');

        $this->assertDatabaseCount('pic_guides', count(PicGuideCatalog::modules()));
    }

    public function test_user_can_customize_and_disable_pic_guides(): void
    {
        $guides = [];

        foreach (PicGuideCatalog::modules() as $module => $label) {
            $guides[$module] = [
                'department' => "Divisi {$label}",
                'input_role' => "Input {$label}",
                'review_role' => "Review {$label}",
                'is_enabled' => $module === 'sales-orders' ? '0' : '1',
            ];
        }

        $this->put(route('pic-guides.update'), ['guides' => $guides])
            ->assertRedirect(route('pic-guides.index'))
            ->assertSessionHas('status');

        $salesOrderGuide = PicGuide::query()->where('module', 'sales-orders')->firstOrFail();
        $this->assertSame('Divisi Sales Order', $salesOrderGuide->department);
        $this->assertSame('Input Sales Order', $salesOrderGuide->input_role);
        $this->assertFalse($salesOrderGuide->is_enabled);

        $this->get(route('sales-orders.create'))
            ->assertOk()
            ->assertDontSee('PIC umum (acuan belajar)');

        PicGuide::query()->where('module', 'sales-orders')->update(['is_enabled' => true]);

        $this->get(route('sales-orders.create'))
            ->assertOk()
            ->assertSee('Divisi: Divisi Sales Order')
            ->assertSee('Penginput: Input Sales Order')
            ->assertSee('Pemeriksa/penanggung jawab: Review Sales Order');
    }
}
