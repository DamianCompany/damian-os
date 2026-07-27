<?php

namespace Tests\Feature;

use App\Filament\Resources\Printers\PrinterResource;
use App\Models\Printer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupervisorAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_dashboard_is_operational_and_printers_are_read_only(): void
    {
        $supervisor = User::factory()->create([
            'role' => 'dami_3d',
            'is_active' => true,
        ]);
        $printer = Printer::factory()->create([
            'name' => 'DAMI-3D-QA',
            'location' => 'Taller QA',
            'status' => 'available',
            'is_active' => true,
        ]);

        $this->actingAs($supervisor);

        $this->assertFalse(PrinterResource::canCreate());
        $this->assertFalse(PrinterResource::canEdit($printer));
        $this->assertFalse(PrinterResource::canDelete($printer));
        $this->assertFalse(PrinterResource::canDeleteAny());
    }

    public function test_gerencia_keeps_printer_management_permissions(): void
    {
        $manager = User::factory()->create([
            'role' => 'gerencia',
            'is_active' => true,
        ]);
        $printer = Printer::factory()->create([
            'name' => 'DAMI-3D-QA',
            'location' => 'Taller QA',
            'status' => 'available',
            'is_active' => true,
        ]);

        $this->actingAs($manager);

        $this->assertTrue(PrinterResource::canCreate());
        $this->assertTrue(PrinterResource::canEdit($printer));
        $this->assertTrue(PrinterResource::canDelete($printer));
        $this->assertTrue(PrinterResource::canDeleteAny());
    }
}
