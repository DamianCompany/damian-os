<?php

namespace Tests\Feature;

use App\Filament\Resources\DamiOrders\DamiOrderResource;
use App\Filament\Resources\Printers\PrinterResource;
use App\Filament\Resources\SolicitudesAutomation\SolicitudAutomationResource;
use App\Filament\Resources\SolicitudesInvestiga\SolicitudInvestigaResource;
use App\Filament\Resources\Users\UserResource;
use App\Http\Responses\LoginResponse;
use App\Models\Printer;
use App\Models\SolicitudAutomation;
use App\Models\SolicitudInvestiga;
use App\Models\User;
use Filament\Models\Contracts\FilamentUser;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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

    public function test_automation_supervisor_only_sees_and_manages_its_area(): void
    {
        $supervisor = User::factory()->create([
            'name' => 'Supervisor Automation',
            'role' => 'automation',
            'is_active' => true,
        ]);

        $this->actingAs($supervisor);

        $this->assertTrue(SolicitudAutomationResource::canViewAny());
        $this->assertTrue(SolicitudAutomationResource::canCreate());
        $this->assertFalse(SolicitudInvestigaResource::canViewAny());
        $this->assertFalse(DamiOrderResource::canViewAny());
        $this->assertFalse(PrinterResource::canViewAny());

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Damian Automation')
            ->assertSee('Nueva solicitud');

        $this->get(SolicitudAutomationResource::getUrl())
            ->assertOk()
            ->assertSee('Solicitudes y proyectos');
    }

    public function test_automation_request_generates_code_audit_and_progressive_record(): void
    {
        $supervisor = User::factory()->create([
            'role' => 'automation',
            'is_active' => true,
        ]);
        $this->actingAs($supervisor);

        $solicitud = SolicitudAutomation::query()->create([
            'cliente' => 'Industria de prueba',
            'titulo' => 'Automatización de línea de envasado',
            'tipo_servicio' => 'plc_hmi',
            'necesidad' => 'Se necesita automatizar el control y monitoreo de una línea industrial.',
            'resultado_esperado' => 'Tablero, PLC y HMI operativos',
            'estado' => 'solicitud',
        ]);

        $this->assertMatchesRegularExpression('/^DAT-SOL-\d{4}-\d{4}$/', $solicitud->codigo);
        $this->assertSame($supervisor->id, $solicitud->responsable_id);
        $this->assertDatabaseHas('automation_historial_estados', [
            'solicitud_id' => $solicitud->id,
            'estado_nuevo' => 'solicitud',
            'cambiado_por' => $supervisor->id,
        ]);

        $this->get(SolicitudAutomationResource::getUrl('view', ['record' => $solicitud]))
            ->assertOk()
            ->assertSeeInOrder(['Solicitud', 'Alcance', 'Cotización', 'Proyecto', 'Pruebas', 'Entrega y soporte'])
            ->assertSee('Continuar proyecto');

        $this->get(SolicitudAutomationResource::getUrl('edit', ['record' => $solicitud]))
            ->assertOk()
            ->assertSee('Gestionar proyecto de Automation');
    }

    public function test_gerencia_can_consult_automation_but_cannot_modify_it(): void
    {
        $gerencia = User::factory()->create(['role' => 'gerencia', 'is_active' => true]);
        $solicitud = SolicitudAutomation::query()->create([
            'cliente' => 'Cliente gerencial',
            'titulo' => 'Máquina de inspección',
            'tipo_servicio' => 'maquina',
            'necesidad' => 'Se requiere diseñar una máquina para inspección de productos.',
            'resultado_esperado' => 'Máquina validada',
            'estado' => 'solicitud',
        ]);

        $this->actingAs($gerencia);
        $this->assertTrue(SolicitudAutomationResource::canViewAny());
        $this->assertFalse(SolicitudAutomationResource::canCreate());
        $this->assertFalse(SolicitudAutomationResource::canEdit($solicitud));
        $this->get(SolicitudAutomationResource::getUrl('view', ['record' => $solicitud]))->assertOk();
        $this->get(SolicitudAutomationResource::getUrl('edit', ['record' => $solicitud]))->assertForbidden();
        $this->get('/admin')->assertOk()->assertSee('Damian Automation');
    }

    public function test_login_discards_previous_forbidden_destination_and_opens_dashboard(): void
    {
        $session = app('session')->driver();
        $session->start();
        $session->put('url.intended', url('/admin/automation/solicitudes/999/gestionar'));

        $request = Request::create('/admin/login', 'POST');
        $request->setLaravelSession($session);

        $loginResponse = app(LoginResponseContract::class);
        $this->assertInstanceOf(LoginResponse::class, $loginResponse);

        $response = $loginResponse->toResponse($request);

        $this->assertSame(url('/admin'), $response->getTargetUrl());
        $this->assertNull($session->get('url.intended'));
    }
}
