<?php

namespace Tests\Feature;

use App\Filament\Resources\DamiOrders\DamiOrderResource;
use App\Filament\Resources\Printers\PrinterResource;
use App\Filament\Resources\SolicitudesInvestiga\SolicitudInvestigaResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Printer;
use App\Models\SolicitudInvestiga;
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

    public function test_investiga_supervisor_only_sees_its_operational_area(): void
    {
        $supervisor = User::factory()->create([
            'role' => 'investiga_lab',
            'is_active' => true,
        ]);

        $this->actingAs($supervisor);

        $this->assertTrue(SolicitudInvestigaResource::canViewAny());
        $this->assertTrue(SolicitudInvestigaResource::canCreate());
        $this->assertFalse(DamiOrderResource::canViewAny());
        $this->assertFalse(PrinterResource::canViewAny());
        $this->assertFalse(UserResource::canViewAny());
    }

    public function test_investiga_supervisor_can_open_its_dashboard_and_request_list(): void
    {
        $supervisor = User::factory()->create([
            'name' => 'Supervisor Investiga',
            'role' => 'investiga_lab',
            'is_active' => true,
        ]);

        $this->actingAs($supervisor)
            ->get('/admin')
            ->assertOk()
            ->assertSee('InvestigaLab')
            ->assertSee('Nueva idea');

        $this->get(SolicitudInvestigaResource::getUrl())
            ->assertOk()
            ->assertSee('Ideas y solicitudes');
    }

    public function test_gerencia_can_consult_investiga_without_creating_requests(): void
    {
        $gerencia = User::factory()->create([
            'role' => 'gerencia',
            'is_active' => true,
        ]);

        $this->actingAs($gerencia);

        $this->assertTrue(SolicitudInvestigaResource::canViewAny());
        $this->assertFalse(SolicitudInvestigaResource::canCreate());

        $this->get('/admin')
            ->assertOk()
            ->assertSeeInOrder(['DAMI 3D', 'InvestigaLab'])
            ->assertSee('Producción, pedidos e impresoras del área.')
            ->assertSee('Ideas, evaluaciones y proyectos de investigación.')
            ->assertSee('Seguimiento DAMI 3D')
            ->assertSee('InvestigaLab')
            ->assertSee('Consultar área');
    }

    public function test_registering_an_investiga_request_creates_its_code_and_audit_event(): void
    {
        $supervisor = User::factory()->create([
            'role' => 'investiga_lab',
            'is_active' => true,
        ]);

        $this->actingAs($supervisor);

        $solicitud = SolicitudInvestiga::query()->create([
            'solicitante' => 'Universidad de prueba',
            'titulo' => 'Sistema de monitoreo ambiental',
            'sector' => 'ambiente',
            'tipo_proyecto' => 'investigacion',
            'problema_necesidad' => 'Se necesita medir la calidad del aire en zonas urbanas.',
            'resultado_esperado' => 'Informe y prototipo',
            'confidencialidad' => 'normal',
            'estado' => 'idea_registrada',
        ]);

        $this->assertMatchesRegularExpression('/^DIL-SOL-\d{4}-\d{4}$/', $solicitud->codigo);
        $this->assertSame($supervisor->id, $solicitud->responsable_id);
        $this->assertDatabaseHas('investiga_historial_estados', [
            'solicitud_id' => $solicitud->id,
            'estado_nuevo' => 'idea_registrada',
            'cambiado_por' => $supervisor->id,
        ]);
    }

    public function test_investiga_project_stages_are_visible_and_only_supervisor_can_manage_them(): void
    {
        $supervisor = User::factory()->create([
            'role' => 'investiga_lab',
            'is_active' => true,
        ]);
        $solicitud = SolicitudInvestiga::query()->create([
            'solicitante' => 'Laboratorio de prueba',
            'titulo' => 'Prototipo para control ambiental',
            'sector' => 'ambiente',
            'tipo_proyecto' => 'prototipo',
            'problema_necesidad' => 'Se requiere validar una solución para monitoreo ambiental.',
            'resultado_esperado' => 'Prototipo validado',
            'confidencialidad' => 'normal',
            'estado' => 'idea_registrada',
        ]);

        $this->actingAs($supervisor)
            ->get(SolicitudInvestigaResource::getUrl('view', ['record' => $solicitud]))
            ->assertOk()
            ->assertSeeInOrder(['Solicitud y definición', 'Planificación', 'Datos', 'Experimentos', 'Resultados y cierre'])
            ->assertSee('Continuar proyecto');

        $this->get(SolicitudInvestigaResource::getUrl('edit', ['record' => $solicitud]))
            ->assertOk()
            ->assertSee('Gestionar proyecto')
            ->assertSee('Definición')
            ->assertSee('Planificación');

        $gerencia = User::factory()->create([
            'role' => 'gerencia',
            'is_active' => true,
        ]);

        $this->actingAs($gerencia);
        $this->assertFalse(SolicitudInvestigaResource::canEdit($solicitud));
        $this->get(SolicitudInvestigaResource::getUrl('edit', ['record' => $solicitud]))
            ->assertForbidden();
    }
}
