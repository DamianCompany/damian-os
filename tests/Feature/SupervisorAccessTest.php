<?php

namespace Tests\Feature;

use App\Filament\Resources\DamiOrders\DamiOrderResource;
use App\Filament\Resources\Printers\PrinterResource;
use App\Filament\Resources\ProveedoresDami3d\ProveedorDami3dResource;
use App\Filament\Resources\ProveedoresDami3d\Pages\CrearProveedorDami3d;
use App\Filament\Resources\ProveedoresDami3d\Pages\VerProveedorDami3d;
use App\Filament\Resources\CategoriasProveedorDami3d\CategoriaProveedorDami3dResource;
use App\Filament\Resources\OrdenesServicioTecnico\OrdenServicioTecnicoResource;
use App\Filament\Resources\ProveedoresServicioTecnico\Pages\CrearProveedorServicioTecnico;
use App\Filament\Resources\ProveedoresServicioTecnico\ProveedorServicioTecnicoResource;
use App\Filament\Resources\CategoriasProveedorServicioTecnico\CategoriaProveedorServicioTecnicoResource;
use App\Filament\Resources\MarcasProveedorServicioTecnico\MarcaProveedorServicioTecnicoResource;
use App\Filament\Resources\SolicitudesAutomation\SolicitudAutomationResource;
use App\Filament\Resources\SolicitudesInvestiga\SolicitudInvestigaResource;
use App\Filament\Resources\Users\UserResource;
use App\Http\Responses\LoginResponse;
use App\Models\DamiOrder;
use App\Models\Printer;
use App\Models\ProductoProveedorDami3d;
use App\Models\ProveedorDami3d;
use App\Models\CategoriaProveedorDami3d;
use App\Models\OrdenServicioTecnico;
use App\Models\CategoriaProveedorServicioTecnico;
use App\Models\MarcaProveedorServicioTecnico;
use App\Models\ProveedorServicioTecnico;
use App\Models\SolicitudAutomation;
use App\Models\SolicitudInvestiga;
use App\Models\User;
use Filament\Models\Contracts\FilamentUser;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Livewire\Livewire;
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

    public function test_servicio_tecnico_supervisor_only_manages_its_area(): void
    {
        $supervisor = User::factory()->create([
            'name' => 'Supervisor Servicio Técnico',
            'role' => 'servicio_tecnico',
            'is_active' => true,
        ]);

        $this->actingAs($supervisor);

        $this->assertTrue(OrdenServicioTecnicoResource::canViewAny());
        $this->assertTrue(OrdenServicioTecnicoResource::canCreate());
        $this->assertFalse(DamiOrderResource::canViewAny());
        $this->assertFalse(SolicitudInvestigaResource::canViewAny());
        $this->assertFalse(SolicitudAutomationResource::canViewAny());

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Servicio Técnico')
            ->assertSee('Recibir equipo');

        $this->get(OrdenServicioTecnicoResource::getUrl())
            ->assertOk()
            ->assertSee('Órdenes de servicio');
    }

    public function test_servicio_tecnico_order_generates_code_and_state_audit(): void
    {
        $supervisor = User::factory()->create(['role' => 'servicio_tecnico', 'is_active' => true]);
        $this->actingAs($supervisor);

        $categoria = CategoriaProveedorServicioTecnico::query()->where('nombre', 'Equipos')->firstOrFail();
        $marca = MarcaProveedorServicioTecnico::query()->where('nombre', 'HONDA')->firstOrFail();
        $proveedor = ProveedorServicioTecnico::query()->create([
            'razon_social' => 'Proveedor de la orden',
            'estado' => 'activo',
        ]);
        $proveedor->categorias()->attach($categoria);
        $proveedor->marcas()->attach($marca);

        $orden = OrdenServicioTecnico::query()->create([
            'cliente' => 'Cliente de prueba',
            'telefono' => '999888777',
            'tipo_equipo' => 'Impresora 3D',
            'categoria_servicio_tecnico_id' => $categoria->getKey(),
            'marca_servicio_tecnico_id' => $marca->getKey(),
            'proveedor_servicio_tecnico_id' => $proveedor->getKey(),
            'marca' => $marca->nombre,
            'tipo_atencion' => 'mantenimiento',
            'falla_reportada' => 'El equipo no enciende.',
            'prioridad' => 'normal',
            'estado' => 'ingresado',
        ]);

        $this->assertMatchesRegularExpression('/^STD-OT-\d{4}-\d{4}$/', $orden->codigo);
        $this->assertSame($supervisor->id, $orden->responsable_id);
        $this->assertDatabaseHas('servicio_tecnico_historial_estados', [
            'orden_id' => $orden->id,
            'estado_nuevo' => 'ingresado',
            'cambiado_por' => $supervisor->id,
        ]);

        $this->get(OrdenServicioTecnicoResource::getUrl('view', ['record' => $orden]))
            ->assertOk()
            ->assertSee('Iniciar diagnóstico')
            ->assertSee('Completar información')
            ->assertSee('Equipos')
            ->assertSee('HONDA')
            ->assertSee('Proveedor de la orden');

        $this->get(OrdenServicioTecnicoResource::getUrl('edit', ['record' => $orden]))
            ->assertOk()
            ->assertSee('Diagnóstico y cotización')
            ->assertSee('Mantenimiento')
            ->assertSee('Prueba y entrega');

        $orden->update([
            'conceptos_mantenimiento' => [[
                'descripcion' => 'Mantenimiento preventivo',
                'cantidad' => 1,
                'precio_unitario' => 20,
            ]],
            'base_imponible' => 16.95,
            'igv_incluido' => 3.05,
            'precio_cotizado' => 20,
        ]);

        $this->get(route('servicio-tecnico.cotizacion.pdf', $orden))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_servicio_tecnico_supervisor_can_manage_technical_suppliers_and_catalogs(): void
    {
        $supervisor = User::factory()->create(['role' => 'servicio_tecnico', 'is_active' => true]);
        $this->actingAs($supervisor);

        $this->assertTrue(ProveedorServicioTecnicoResource::canViewAny());
        $this->assertTrue(ProveedorServicioTecnicoResource::canCreate());
        $this->assertTrue(CategoriaProveedorServicioTecnicoResource::canCreate());
        $this->assertTrue(MarcaProveedorServicioTecnicoResource::canCreate());
        $this->assertSame(11, CategoriaProveedorServicioTecnico::query()->count());
        $this->assertSame(9, MarcaProveedorServicioTecnico::query()->count());
        $this->assertDatabaseHas('categorias_proveedor_servicio_tecnico', ['nombre' => 'Electrónica e IoT']);
        $this->assertDatabaseHas('marcas_proveedor_servicio_tecnico', ['nombre' => 'Husqvarna']);

        $categoria = CategoriaProveedorServicioTecnico::query()->where('nombre', 'Accesorios y repuestos')->firstOrFail();
        $marca = MarcaProveedorServicioTecnico::query()->where('nombre', 'HONDA')->firstOrFail();

        Livewire::test(CrearProveedorServicioTecnico::class)
            ->fillForm([
                'tipo' => 'distribuidor',
                'razon_social' => 'Repuestos Técnicos Damian',
                'contacto_nombre' => 'Asesor técnico',
                'whatsapp' => '999888777',
                'categorias' => [$categoria->getKey()],
                'marcas' => [$marca->getKey()],
                'productos_principales' => 'Repuestos para motores Honda.',
                'estado' => 'activo',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $proveedor = ProveedorServicioTecnico::query()->where('razon_social', 'Repuestos Técnicos Damian')->firstOrFail();
        $this->assertMatchesRegularExpression('/^PST-\d{4}-\d{4}$/', $proveedor->codigo);
        $this->assertTrue($proveedor->categorias()->whereKey($categoria->getKey())->exists());
        $this->assertTrue($proveedor->marcas()->whereKey($marca->getKey())->exists());

        $this->get(ProveedorServicioTecnicoResource::getUrl())->assertOk()->assertSee('Proveedores de Servicio Técnico');
        $this->get(ProveedorServicioTecnicoResource::getUrl('view', ['record' => $proveedor]))
            ->assertOk()
            ->assertSee('Ficha del proveedor')
            ->assertSee('Repuestos Técnicos Damian')
            ->assertSee('Accesorios y repuestos')
            ->assertSee('HONDA');
        $this->get(CategoriaProveedorServicioTecnicoResource::getUrl())->assertOk()->assertSee('Agricultura');
        $this->get(MarcaProveedorServicioTecnicoResource::getUrl())->assertOk()->assertSee('Stihl');
    }

    public function test_gerencia_can_consult_servicio_tecnico_but_cannot_modify_it(): void
    {
        $gerencia = User::factory()->create(['role' => 'gerencia', 'is_active' => true]);
        $orden = OrdenServicioTecnico::query()->create([
            'cliente' => 'Cliente gerencial',
            'telefono' => '999888777',
            'tipo_equipo' => 'Máquina industrial',
            'falla_reportada' => 'Presenta una parada inesperada.',
            'estado' => 'ingresado',
        ]);

        $this->actingAs($gerencia);
        $this->assertTrue(OrdenServicioTecnicoResource::canViewAny());
        $this->assertFalse(OrdenServicioTecnicoResource::canCreate());
        $this->assertFalse(OrdenServicioTecnicoResource::canEdit($orden));
        $this->assertTrue(ProveedorServicioTecnicoResource::canViewAny());
        $this->assertFalse(ProveedorServicioTecnicoResource::canCreate());
        $this->assertFalse(CategoriaProveedorServicioTecnicoResource::canCreate());
        $this->assertFalse(MarcaProveedorServicioTecnicoResource::canCreate());
        $this->get(OrdenServicioTecnicoResource::getUrl('view', ['record' => $orden]))->assertOk();
        $this->get(OrdenServicioTecnicoResource::getUrl('edit', ['record' => $orden]))->assertForbidden();
        $this->get('/admin')->assertOk()->assertSee('Servicio Técnico');
    }

    public function test_dami_3d_supervisor_can_manage_suppliers_without_sensitive_configuration(): void
    {
        $supervisor=User::factory()->create(['role'=>'dami_3d','is_active'=>true]);
        $this->actingAs($supervisor);
        $this->assertTrue(ProveedorDami3dResource::canViewAny());
        $this->assertTrue(ProveedorDami3dResource::canCreate());
        $this->assertFalse(CategoriaProveedorDami3dResource::canViewAny());
        $this->get(ProveedorDami3dResource::getUrl('create'))->assertOk()->assertSee('Qué suministra')->assertDontSee('Datos bancarios restringidos');

        $proveedor=ProveedorDami3d::create(['tipo'=>'empresa','razon_social'=>'Filamentos de prueba SAC','tipo_documento'=>'ruc','numero_documento'=>'20123456789','whatsapp'=>'999888777','estado'=>'evaluacion']);
        $categoria=CategoriaProveedorDami3d::query()->first();
        $proveedor->categorias()->attach($categoria);

        $this->assertMatchesRegularExpression('/^PROV-\d{4}-\d{4}$/',$proveedor->codigo);
        $this->get(ProveedorDami3dResource::getUrl())->assertOk()->assertSee('Proveedores de DAMI 3D');
        $this->get(ProveedorDami3dResource::getUrl('view',['record'=>$proveedor]))
            ->assertOk()
            ->assertSee('Ficha del proveedor')
            ->assertSee('Filamentos de prueba SAC')
            ->assertSee('Registrar producto')
            ->assertSee('Más acciones')
            ->assertDontSee('Cambiar estado');

        Livewire::test(VerProveedorDami3d::class, ['record' => $proveedor->getRouteKey()])
            ->callAction('producto', [
                'nombre' => 'Filamento PLA blanco',
                'categoria_id' => $categoria->getKey(),
                'precio_referencial' => 89.90,
                'moneda' => 'PEN',
                'disponibilidad' => 'disponible',
                'igv_incluido' => true,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('productos_proveedor_dami3d', [
            'proveedor_id' => $proveedor->getKey(),
            'nombre' => 'Filamento PLA blanco',
        ]);

        $producto = ProductoProveedorDami3d::query()
            ->where('proveedor_id', $proveedor->getKey())
            ->where('nombre', 'Filamento PLA blanco')
            ->firstOrFail();
        $impresora = Printer::query()->create([
            'name' => 'DAMI-3D-TEST',
            'location' => 'Taller de pruebas',
            'status' => 'available',
            'is_active' => true,
        ]);
        $orden = DamiOrder::factory()->create([
            'producto_proveedor_dami3d_id' => $producto->getKey(),
            'printer_id' => $impresora->getKey(),
        ]);

        $this->assertSame($proveedor->getKey(), $orden->fresh()->proveedor_dami3d_id);
        $this->get(DamiOrderResource::getUrl('view', ['record' => $orden]))
            ->assertOk()
            ->assertSee('Filamentos de prueba SAC')
            ->assertSee('Filamento PLA blanco');

        $this->get(ProveedorDami3dResource::getUrl('edit',['record'=>$proveedor]))->assertOk()->assertDontSee('Datos bancarios restringidos');
    }

    public function test_dami_3d_supervisor_can_create_a_supplier_from_the_wizard(): void
    {
        $supervisor = User::factory()->create(['role' => 'dami_3d', 'is_active' => true]);
        $categoria = CategoriaProveedorDami3d::query()->firstOrFail();

        $this->actingAs($supervisor);

        Livewire::test(CrearProveedorDami3d::class)
            ->fillForm([
                'tipo' => 'distribuidor',
                'razon_social' => 'Proveedor desde asistente',
                'categorias' => [$categoria->getKey()],
                'moneda' => 'PEN',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $proveedor = ProveedorDami3d::query()
            ->where('razon_social', 'Proveedor desde asistente')
            ->firstOrFail();

        $this->assertTrue($proveedor->categorias()->whereKey($categoria->getKey())->exists());
    }

    public function test_gerencia_has_full_supplier_configuration_access(): void
    {
        $gerencia=User::factory()->create(['role'=>'gerencia','is_active'=>true]);
        $this->actingAs($gerencia);
        $this->assertTrue(ProveedorDami3dResource::canViewAny());
        $this->assertTrue(CategoriaProveedorDami3dResource::canViewAny());
        $this->get(CategoriaProveedorDami3dResource::getUrl())->assertOk();

        $proveedor=ProveedorDami3d::create(['tipo'=>'distribuidor','razon_social'=>'Proveedor gerencial','estado'=>'activo']);
        $this->get(ProveedorDami3dResource::getUrl('view',['record'=>$proveedor]))->assertOk()->assertSee('Cambiar estado');
        $this->get(ProveedorDami3dResource::getUrl('edit',['record'=>$proveedor]))->assertOk()->assertSee('Datos bancarios restringidos');
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
