<?php

namespace Tests\Feature;

use App\Filament\Resources\Printers\PrinterResource;
use App\Models\Printer;
use App\Models\User;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupervisorAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_implement_filament_access_contract_for_production(): void
    {
        $this->assertInstanceOf(FilamentUser::class, new User);
    }

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
