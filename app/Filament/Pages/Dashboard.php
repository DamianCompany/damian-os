<?php

namespace App\Filament\Pages;

use App\Filament\Resources\DamiOrders\DamiOrderResource;
use App\Filament\Resources\Printers\PrinterResource;
use App\Filament\Resources\OrdenesServicioTecnico\OrdenServicioTecnicoResource;
use App\Filament\Resources\SolicitudesAutomation\SolicitudAutomationResource;
use App\Filament\Resources\SolicitudesInvestiga\SolicitudInvestigaResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\DamiOrder;
use App\Models\Printer;
use App\Models\OrdenServicioTecnico;
use App\Models\SolicitudAutomation;
use App\Models\SolicitudInvestiga;
use App\Models\User;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Url;

class Dashboard extends BaseDashboard
{
    #[Url(as: 'buscar', except: '')]
    public string $orderSearch = '';

    protected string $view = 'filament.pages.dashboard';

    protected static ?string $navigationLabel = 'Inicio';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    public function getTitle(): string|Htmlable
    {
        return match (auth()->user()?->role) {
            'dami_3d' => 'Panel DAMI 3D',
            'investiga_lab' => 'Panel InvestigaLab',
            'automation' => 'Panel Damian Automation',
            'servicio_tecnico' => 'Panel Servicio Técnico',
            default => 'Panel de Gerencia',
        };
    }

    protected function getViewData(): array
    {
        if (auth()->user()?->role === 'investiga_lab') {
            return $this->getInvestigaViewData();
        }

        if (auth()->user()?->role === 'automation') {
            return $this->getAutomationViewData();
        }

        if (auth()->user()?->role === 'servicio_tecnico') {
            return $this->getServicioTecnicoViewData();
        }

        $orders = DamiOrder::query();
        $pendingStatuses = ['pending'];
        $inProgressStatuses = ['in_progress'];
        $completedStatuses = ['completed'];

        $pendingOrders = (clone $orders)->whereIn('status', $pendingStatuses)->count();
        $inProgressOrders = (clone $orders)->whereIn('status', $inProgressStatuses)->count();
        $completedOrders = (clone $orders)->whereIn('status', $completedStatuses)->count();
        $totalOrders = $pendingOrders + $inProgressOrders + $completedOrders;
        $activeOrders = $pendingOrders + $inProgressOrders;
        $completionRate = $totalOrders > 0 ? (int) round(($completedOrders / $totalOrders) * 100) : 0;

        $totalPrinters = Printer::query()->where('is_active', true)->count();
        $availablePrinters = Printer::query()
            ->where('is_active', true)
            ->where('status', 'available')
            ->count();

        $overdueOrders = DamiOrder::query()
            ->whereNotIn('status', $completedStatuses)
            ->whereDate('delivery_date', '<', today())
            ->count();
        $unavailablePrinters = Printer::query()
            ->where('is_active', true)
            ->whereIn('status', ['maintenance', 'out_of_service'])
            ->count();
        $expiringCredentials = User::query()
            ->where('is_active', true)
            ->whereNotNull('credential_expires_at')
            ->whereBetween('credential_expires_at', [now(), now()->addDays(7)])
            ->count();

        $alerts = collect([
            ['label' => 'Pedidos fuera de fecha', 'detail' => "{$overdueOrders} requieren seguimiento", 'count' => $overdueOrders, 'tone' => 'danger'],
            ['label' => 'Impresoras no disponibles', 'detail' => "{$unavailablePrinters} requieren revisión", 'count' => $unavailablePrinters, 'tone' => 'info'],
            ['label' => 'Credenciales por vencer', 'detail' => "{$expiringCredentials} vencen en los próximos 7 días", 'count' => $expiringCredentials, 'tone' => 'info'],
        ])->filter(fn (array $alert): bool => $alert['count'] > 0);

        $recentOrders = DamiOrder::query()
            ->where('status', '!=', 'completed')
            ->when(filled($this->orderSearch), function ($query): void {
                $term = trim($this->orderSearch);

                $query->where(function ($query) use ($term): void {
                    $query
                        ->where('order_number', 'like', "%{$term}%")
                        ->orWhere('client_name', 'like', "%{$term}%")
                        ->orWhere('client_document', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
            })
            ->latest()
            ->limit(5)
            ->get();

        $upcomingOrders = DamiOrder::query()
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('delivery_date')
            ->orderBy('delivery_date')
            ->limit(5)
            ->get();

        return [
            'isSupervisor' => auth()->user()?->role === 'dami_3d',
            'isInvestigaSupervisor' => false,
            'isAutomationSupervisor' => false,
            'isServicioTecnicoSupervisor' => false,
            'activeOrders' => $activeOrders,
            'completionRate' => $completionRate,
            'totalPrinters' => $totalPrinters,
            'availablePrinters' => $availablePrinters,
            'attentionCount' => $alerts->count(),
            'progress' => [
                ['label' => 'Pendientes', 'detail' => 'Aún no iniciados', 'count' => $pendingOrders, 'tone' => 'blue'],
                ['label' => 'En proceso', 'detail' => 'Actualmente en atención', 'count' => $inProgressOrders, 'tone' => 'green'],
                ['label' => 'Completados', 'detail' => 'Terminados o entregados', 'count' => $completedOrders, 'tone' => 'teal'],
            ],
            'recentOrders' => $recentOrders,
            'upcomingOrders' => $upcomingOrders,
            'printers' => Printer::query()
                ->where('is_active', true)
                ->orderByRaw("CASE status WHEN 'out_of_service' THEN 1 WHEN 'maintenance' THEN 2 WHEN 'in_use' THEN 3 ELSE 4 END")
                ->limit(4)
                ->get(),
            'alerts' => $alerts,
            'investiga' => [
                'ideas' => SolicitudInvestiga::query()->where('estado', 'idea_registrada')->count(),
                'evaluacion' => SolicitudInvestiga::query()->where('estado', 'en_evaluacion')->count(),
                'activos' => SolicitudInvestiga::query()->where('estado', 'proyecto_activo')->count(),
                'total' => SolicitudInvestiga::query()->count(),
                'url' => SolicitudInvestigaResource::getUrl(),
            ],
            'automation' => [
                'solicitudes' => SolicitudAutomation::query()->whereIn('estado', ['solicitud', 'en_evaluacion'])->count(),
                'cotizaciones' => SolicitudAutomation::query()->whereIn('estado', ['cotizacion_enviada', 'esperando_aprobacion'])->count(),
                'activos' => SolicitudAutomation::query()->whereIn('estado', ['proyecto_activo', 'en_ejecucion', 'en_pruebas'])->count(),
                'total' => SolicitudAutomation::query()->count(),
                'url' => SolicitudAutomationResource::getUrl(),
            ],
            'servicioTecnico' => [
                'porDiagnosticar' => OrdenServicioTecnico::query()->whereIn('estado', ['ingresado', 'en_diagnostico'])->count(),
                'enReparacion' => OrdenServicioTecnico::query()->whereIn('estado', ['esperando_repuesto', 'en_reparacion', 'en_pruebas'])->count(),
                'listos' => OrdenServicioTecnico::query()->where('estado', 'listo_entrega')->count(),
                'total' => OrdenServicioTecnico::query()->count(),
                'url' => OrdenServicioTecnicoResource::getUrl(),
            ],
            'urls' => [
                'orders' => DamiOrderResource::getUrl(),
                'createOrder' => DamiOrderResource::getUrl('create'),
                'filteredOrders' => DamiOrderResource::getUrl(parameters: array_filter([
                    'tableSearch' => trim($this->orderSearch),
                ])),
                'printers' => PrinterResource::getUrl(),
                'createCredential' => UserResource::getUrl('create'),
            ],
        ];
    }

    protected function getInvestigaViewData(): array
    {
        $solicitudes = SolicitudInvestiga::query();

        return [
            'isSupervisor' => false,
            'isInvestigaSupervisor' => true,
            'isAutomationSupervisor' => false,
            'isServicioTecnicoSupervisor' => false,
            'investigaResumen' => [
                [
                    'label' => 'Ideas registradas',
                    'detail' => 'Pendientes de revisión',
                    'count' => (clone $solicitudes)->where('estado', 'idea_registrada')->count(),
                    'icon' => 'heroicon-o-light-bulb',
                    'tone' => 'blue',
                ],
                [
                    'label' => 'En evaluación',
                    'detail' => 'Revisando factibilidad',
                    'count' => (clone $solicitudes)->where('estado', 'en_evaluacion')->count(),
                    'icon' => 'heroicon-o-magnifying-glass',
                    'tone' => 'teal',
                ],
                [
                    'label' => 'Proyectos activos',
                    'detail' => 'Aprobados para trabajar',
                    'count' => (clone $solicitudes)->where('estado', 'proyecto_activo')->count(),
                    'icon' => 'heroicon-o-beaker',
                    'tone' => 'green',
                ],
            ],
            'solicitudesRecientes' => SolicitudInvestiga::query()
                ->latest()
                ->limit(5)
                ->get(),
            'fechasProximasInvestiga' => SolicitudInvestiga::query()
                ->whereNotNull('fecha_requerida')
                ->whereNotIn('estado', ['cerrado'])
                ->orderBy('fecha_requerida')
                ->limit(5)
                ->get(),
            'urls' => [
                'solicitudesInvestiga' => SolicitudInvestigaResource::getUrl(),
                'crearSolicitudInvestiga' => SolicitudInvestigaResource::getUrl('create'),
            ],
        ];
    }

    protected function getAutomationViewData(): array
    {
        $solicitudes = SolicitudAutomation::query();

        return [
            'isSupervisor' => false,
            'isInvestigaSupervisor' => false,
            'isAutomationSupervisor' => true,
            'isServicioTecnicoSupervisor' => false,
            'automationResumen' => [
                [
                    'label' => 'Por evaluar',
                    'detail' => 'Solicitudes y alcance',
                    'count' => (clone $solicitudes)->whereIn('estado', ['solicitud', 'en_evaluacion'])->count(),
                    'icon' => 'heroicon-o-inbox-arrow-down',
                    'tone' => 'blue',
                ],
                [
                    'label' => 'Cotizaciones',
                    'detail' => 'Pendientes de decisión',
                    'count' => (clone $solicitudes)->whereIn('estado', ['cotizacion_enviada', 'esperando_aprobacion'])->count(),
                    'icon' => 'heroicon-o-banknotes',
                    'tone' => 'teal',
                ],
                [
                    'label' => 'Proyectos activos',
                    'detail' => 'Ingeniería y ejecución',
                    'count' => (clone $solicitudes)->whereIn('estado', ['proyecto_activo', 'en_ejecucion', 'en_pruebas'])->count(),
                    'icon' => 'heroicon-o-wrench-screwdriver',
                    'tone' => 'green',
                ],
            ],
            'proyectosAutomationRecientes' => SolicitudAutomation::query()->latest()->limit(5)->get(),
            'entregasAutomation' => SolicitudAutomation::query()
                ->whereNotNull('fecha_fin_estimada')
                ->whereNotIn('estado', ['entregado', 'cerrado'])
                ->orderBy('fecha_fin_estimada')
                ->limit(5)
                ->get(),
            'urls' => [
                'solicitudesAutomation' => SolicitudAutomationResource::getUrl(),
                'crearSolicitudAutomation' => SolicitudAutomationResource::getUrl('create'),
            ],
        ];
    }

    protected function getServicioTecnicoViewData(): array
    {
        $ordenes = OrdenServicioTecnico::query();

        return [
            'isSupervisor' => false,
            'isInvestigaSupervisor' => false,
            'isAutomationSupervisor' => false,
            'isServicioTecnicoSupervisor' => true,
            'servicioTecnicoResumen' => [
                [
                    'label' => 'Por diagnosticar',
                    'detail' => 'Ingresados o en revisión',
                    'count' => (clone $ordenes)->whereIn('estado', ['ingresado', 'en_diagnostico'])->count(),
                    'icon' => 'heroicon-o-magnifying-glass',
                    'tone' => 'blue',
                ],
                [
                    'label' => 'En reparación',
                    'detail' => 'Trabajo técnico activo',
                    'count' => (clone $ordenes)->whereIn('estado', ['esperando_repuesto', 'en_reparacion', 'en_pruebas'])->count(),
                    'icon' => 'heroicon-o-wrench-screwdriver',
                    'tone' => 'teal',
                ],
                [
                    'label' => 'Listos para entregar',
                    'detail' => 'Esperando al cliente',
                    'count' => (clone $ordenes)->where('estado', 'listo_entrega')->count(),
                    'icon' => 'heroicon-o-check-circle',
                    'tone' => 'green',
                ],
            ],
            'ordenesServicioRecientes' => OrdenServicioTecnico::query()
                ->whereNotIn('estado', ['entregado'])
                ->latest()
                ->limit(5)
                ->get(),
            'entregasServicioProximas' => OrdenServicioTecnico::query()
                ->whereNotNull('fecha_entrega_estimada')
                ->whereNotIn('estado', ['entregado', 'no_reparado'])
                ->orderBy('fecha_entrega_estimada')
                ->limit(5)
                ->get(),
            'urls' => [
                'ordenesServicioTecnico' => OrdenServicioTecnicoResource::getUrl(),
                'crearOrdenServicioTecnico' => OrdenServicioTecnicoResource::getUrl('create'),
            ],
        ];
    }
}
