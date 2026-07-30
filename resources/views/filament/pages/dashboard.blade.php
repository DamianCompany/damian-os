<x-filament-panels::page>
    <div class="damian-dashboard space-y-6">
        @if ($isAutomationSupervisor)
            @include('filament.pages.partials.dashboard-automation')
        @elseif ($isInvestigaSupervisor)
            <section class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="text-3xl font-semibold tracking-[-.04em] text-[#152036] dark:text-[#f3f3f3]">
                        InvestigaLab
                    </h1>
                    <p class="mt-2 text-base text-[#65738a] dark:text-[#99a5b5]">
                        Hola, <strong class="font-semibold text-[#168fdd] dark:text-[#31bae4]">{{ auth()->user()->name }}</strong>.
                        Revisa las iniciativas del área sin perder de vista lo próximo.
                    </p>
                </div>

                <a href="{{ $urls['crearSolicitudInvestiga'] }}" class="damian-dashboard__action damian-dashboard__action--primary">
                    <x-filament::icon icon="heroicon-o-plus" class="size-5" />
                    Nueva idea
                </a>
            </section>

            <section class="grid gap-4 md:grid-cols-3" aria-label="Resumen de InvestigaLab">
                @foreach ($investigaResumen as $indicador)
                    <article class="damian-supervisor-stat">
                        <span @class([
                            'damian-supervisor-stat__icon',
                            'text-[#1bb1e3] bg-[#1bb1e3]/10' => $indicador['tone'] === 'blue',
                            'text-[#1a4e5c] bg-[#1a4e5c]/10 dark:text-[#31bae4]' => $indicador['tone'] === 'teal',
                            'text-[#22a15e] bg-[#22a15e]/10' => $indicador['tone'] === 'green',
                        ])>
                            <x-filament::icon :icon="$indicador['icon']" class="size-6" />
                        </span>
                        <div>
                            <p>{{ $indicador['label'] }}</p>
                            <strong>{{ $indicador['count'] }}</strong>
                            <span>{{ $indicador['detail'] }}</span>
                        </div>
                    </article>
                @endforeach
            </section>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.55fr)_minmax(18rem,.65fr)]">
                <section class="damian-panel min-w-0">
                    <div class="damian-panel__header">
                        <h2>Ideas recientes</h2>
                        <a href="{{ $urls['solicitudesInvestiga'] }}" class="damian-panel__link">Ver todas</a>
                    </div>

                    @if ($solicitudesRecientes->isEmpty())
                        <div class="damian-empty">
                            <x-filament::icon icon="heroicon-o-light-bulb" class="size-8 text-[#1bb1e3]" />
                            <div>
                                <p class="font-semibold">Aún no hay ideas registradas</p>
                                <p class="mt-1 text-sm text-[#65738a] dark:text-[#99a5b5]">La primera solicitud aparecerá aquí.</p>
                            </div>
                        </div>
                    @else
                        <div class="damian-work-list">
                            @foreach ($solicitudesRecientes as $solicitud)
                                <article class="damian-work-row">
                                    <div class="min-w-0">
                                        <a href="{{ \App\Filament\Resources\SolicitudesInvestiga\SolicitudInvestigaResource::getUrl('view', ['record' => $solicitud]) }}" class="damian-work-row__order">
                                            {{ $solicitud->codigo }}
                                        </a>
                                        <p class="damian-work-row__client">{{ $solicitud->solicitante }}</p>
                                    </div>
                                    <p class="damian-work-row__description">{{ $solicitud->titulo }}</p>
                                    <div class="damian-work-row__delivery">
                                        <span>Fecha requerida</span>
                                        <strong>{{ $solicitud->fecha_requerida?->format('d/m/Y') ?? 'Por definir' }}</strong>
                                    </div>
                                    <span class="damian-status">
                                        {{ \App\Filament\Resources\SolicitudesInvestiga\Tables\TablaSolicitudesInvestiga::etiquetaEstado($solicitud->estado) }}
                                    </span>
                                    <a href="{{ \App\Filament\Resources\SolicitudesInvestiga\SolicitudInvestigaResource::getUrl('view', ['record' => $solicitud]) }}" class="damian-work-row__action">
                                        Ver ficha
                                        <x-filament::icon icon="heroicon-o-arrow-right" class="size-4" />
                                    </a>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>

                <section class="damian-panel">
                    <div class="damian-panel__header">
                        <h2>Fechas próximas</h2>
                    </div>

                    <div class="damian-deadlines">
                        @forelse ($fechasProximasInvestiga as $solicitud)
                            <a href="{{ \App\Filament\Resources\SolicitudesInvestiga\SolicitudInvestigaResource::getUrl('view', ['record' => $solicitud]) }}">
                                <span class="damian-deadlines__icon">
                                    <x-filament::icon icon="heroicon-o-calendar-days" class="size-5" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <strong>{{ \Illuminate\Support\Str::limit($solicitud->titulo, 36) }}</strong>
                                    <small>{{ $solicitud->solicitante }}</small>
                                </span>
                                <span class="damian-deadlines__date">
                                    <strong>{{ $solicitud->fecha_requerida->format('d/m/Y') }}</strong>
                                </span>
                            </a>
                        @empty
                            <div class="damian-empty damian-empty--compact">
                                <p class="font-semibold">Sin fechas próximas</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        @elseif ($isSupervisor)
            <section class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-base text-[#65738a] dark:text-[#99a5b5]">
                        Hola, <strong class="font-semibold text-[#168fdd] dark:text-[#31bae4]">{{ auth()->user()->name }}</strong>. Este es el trabajo activo del área.
                    </p>
                </div>

                <a href="{{ $urls['createOrder'] }}" class="damian-dashboard__action damian-dashboard__action--primary">
                    <x-filament::icon icon="heroicon-o-plus" class="size-5" />
                    Crear pedido
                </a>
            </section>

            <section class="grid gap-4 md:grid-cols-3" aria-label="Estado de pedidos DAMI 3D">
                @foreach ($progress as $stage)
                    @php
                        $stageIcon = match ($stage['label']) {
                            'Pendientes' => 'heroicon-o-clock',
                            'En proceso' => 'heroicon-o-play',
                            default => 'heroicon-o-check',
                        };
                        $stageColor = match ($stage['label']) {
                            'Pendientes' => 'text-[#1bb1e3] bg-[#1bb1e3]/10',
                            'En proceso' => 'text-[#31bae4] bg-[#1bb1e3]/10',
                            default => 'text-[#22a15e] bg-[#22a15e]/10',
                        };
                    @endphp
                    <article class="damian-supervisor-stat">
                        <span class="damian-supervisor-stat__icon {{ $stageColor }}">
                            <x-filament::icon :icon="$stageIcon" class="size-6" />
                        </span>
                        <div>
                            <p>{{ $stage['label'] }}</p>
                            <strong>{{ $stage['count'] }}</strong>
                            <span>{{ $stage['detail'] }}</span>
                        </div>
                    </article>
                @endforeach
            </section>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.55fr)_minmax(18rem,.65fr)]">
                <section class="damian-panel min-w-0">
                    <div class="damian-panel__header">
                        <h2>Trabajo activo</h2>
                        <a href="{{ $urls['orders'] }}" class="damian-panel__link">Ver todos</a>
                    </div>

                    @if ($recentOrders->isEmpty())
                        <div class="damian-empty">
                            <x-filament::icon icon="heroicon-o-check-circle" class="size-8 text-[#22a15e]" />
                            <div>
                                <p class="font-semibold">No hay pedidos por atender</p>
                                <p class="mt-1 text-sm text-[#65738a] dark:text-[#99a5b5]">Los pedidos activos aparecerán aquí.</p>
                            </div>
                        </div>
                    @else
                        <div class="damian-work-list">
                            @foreach ($recentOrders as $order)
                                <article class="damian-work-row">
                                    <div class="min-w-0">
                                        <a href="{{ \App\Filament\Resources\DamiOrders\DamiOrderResource::getUrl('view', ['record' => $order]) }}" class="damian-work-row__order">
                                            {{ $order->order_number }}
                                        </a>
                                        <p class="damian-work-row__client">{{ $order->client_name }}</p>
                                    </div>
                                    <p class="damian-work-row__description">{{ $order->description }}</p>
                                    <div class="damian-work-row__delivery">
                                        <span>Entrega</span>
                                        <strong>{{ $order->delivery_date?->format('d/m/Y') ?: 'Por definir' }}</strong>
                                    </div>
                                    <span class="damian-status damian-status--{{ $order->status }}">
                                        {{ match ($order->status) {
                                            'pending' => 'Pendiente',
                                            'in_progress' => 'En proceso',
                                            default => 'Completado',
                                        } }}
                                    </span>
                                    <a href="{{ \App\Filament\Resources\DamiOrders\DamiOrderResource::getUrl('view', ['record' => $order]) }}" class="damian-work-row__action">
                                        Ver ficha
                                        <x-filament::icon icon="heroicon-o-arrow-right" class="size-4" />
                                    </a>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>

                <section class="damian-panel">
                    <div class="damian-panel__header">
                        <h2>Próximas entregas</h2>
                    </div>

                    <div class="damian-deadlines">
                        @forelse ($upcomingOrders as $order)
                            @php($days = today()->diffInDays($order->delivery_date, false))
                            <a href="{{ \App\Filament\Resources\DamiOrders\DamiOrderResource::getUrl('view', ['record' => $order]) }}">
                                <span class="damian-deadlines__icon">
                                    <x-filament::icon icon="heroicon-o-calendar-days" class="size-5" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <strong>{{ \Illuminate\Support\Str::limit($order->description, 34) }}</strong>
                                    <small>{{ $order->client_name }}</small>
                                </span>
                                <span class="damian-deadlines__date">
                                    <strong>{{ $order->delivery_date->format('d/m/Y') }}</strong>
                                    <small class="{{ $days <= 1 ? 'text-red-500' : ($days <= 3 ? 'text-amber-500' : '') }}">
                                        {{ $days < 0 ? 'Vencido' : ($days === 0 ? 'Hoy' : ($days === 1 ? 'Mañana' : "En {$days} días")) }}
                                    </small>
                                </span>
                            </a>
                        @empty
                            <div class="damian-empty damian-empty--compact">
                                <p class="font-semibold">Sin entregas próximas</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>

            <section class="damian-panel">
                <div class="damian-panel__header">
                    <h2>Impresoras</h2>
                    <a href="{{ $urls['printers'] }}" class="damian-panel__link">Ver impresoras</a>
                </div>

                <div class="damian-printer-strip">
                    @forelse ($printers as $printer)
                        <article>
                            <span class="damian-printer-strip__icon">
                                <x-filament::icon icon="heroicon-o-printer" class="size-5" />
                            </span>
                            <span class="min-w-0">
                                <strong>{{ $printer->name }}</strong>
                                <small>{{ $printer->location }}</small>
                            </span>
                            <span class="damian-printer-strip__status damian-printer-strip__status--{{ $printer->status }}">
                                {{ match ($printer->status) {
                                    'available' => 'Disponible',
                                    'in_use' => 'En uso',
                                    'maintenance' => 'Mantenimiento',
                                    default => 'Fuera de servicio',
                                } }}
                            </span>
                        </article>
                    @empty
                        <p class="text-sm text-[#65738a] dark:text-[#99a5b5]">No hay impresoras registradas.</p>
                    @endforelse
                </div>
            </section>
        @else
        <section class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-[.92rem] font-semibold text-[#22a15e]">Vista general</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-[-.04em] text-[#152036] dark:text-[#f3f3f3]">
                    Hola, {{ auth()->user()->name }}
                </h1>
                <p class="mt-2 max-w-2xl text-[.94rem] leading-6 text-[#65738a] dark:text-[#99a5b5]">
                    Vista consolidada de DAMI 3D, InvestigaLab y Damian Automation.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ $urls['createCredential'] }}" class="damian-dashboard__action damian-dashboard__action--secondary">
                    <x-filament::icon icon="heroicon-o-user-plus" class="size-4" />
                    Nueva credencial
                </a>
                <a href="{{ $urls['printers'] }}" class="damian-dashboard__action damian-dashboard__action--primary">
                    <x-filament::icon icon="heroicon-o-printer" class="size-4" />
                    Impresoras
                </a>
            </div>
        </section>

        <section class="damian-area-overview grid gap-5 xl:grid-cols-3" aria-label="Resumen por áreas">
            <article class="damian-panel border-t-2 border-t-[#1bb1e3]">
                <div class="damian-panel__header">
                    <div class="flex items-center gap-3">
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-[#1bb1e3]/10 text-[#1bb1e3]">
                            <x-filament::icon icon="heroicon-o-cube" class="size-6" />
                        </span>
                        <div>
                            <h2>DAMI 3D</h2>
                            <p>Producción, pedidos e impresoras del área.</p>
                        </div>
                    </div>
                    <a href="{{ $urls['orders'] }}" class="damian-panel__link">Consultar área</a>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <article class="damian-supervisor-stat">
                        <span class="damian-supervisor-stat__icon bg-[#1bb1e3]/10 text-[#168fdd] dark:text-[#31bae4]">
                            <x-filament::icon icon="heroicon-o-clipboard-document-list" class="size-6" />
                        </span>
                        <div><p>Pedidos activos</p><strong>{{ $activeOrders }}</strong><span>Pendientes o en atención</span></div>
                    </article>
                    <article class="damian-supervisor-stat">
                        <span class="damian-supervisor-stat__icon bg-[#22a15e]/10 text-[#22a15e]">
                            <x-filament::icon icon="heroicon-o-chart-bar-square" class="size-6" />
                        </span>
                        <div><p>Avance general</p><strong>{{ $completionRate }}%</strong><span>Pedidos completados</span></div>
                    </article>
                    <article class="damian-supervisor-stat">
                        <span class="damian-supervisor-stat__icon bg-[#1a4e5c]/10 text-[#1a4e5c] dark:bg-[#31bae4]/10 dark:text-[#31bae4]">
                            <x-filament::icon icon="heroicon-o-printer" class="size-6" />
                        </span>
                        <div><p>Impresoras</p><strong>{{ $availablePrinters }} / {{ $totalPrinters }}</strong><span>Disponibles</span></div>
                    </article>
                    <article class="damian-supervisor-stat">
                        <span class="damian-supervisor-stat__icon bg-amber-500/10 text-amber-600 dark:text-amber-400">
                            <x-filament::icon icon="heroicon-o-bell-alert" class="size-6" />
                        </span>
                        <div><p>Atención</p><strong>{{ $attentionCount }}</strong><span>Alertas activas</span></div>
                    </article>
                </div>
            </article>

            <article class="damian-panel border-t-2 border-t-[#22a15e]">
                <div class="damian-panel__header">
                    <div class="flex items-center gap-3">
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-[#22a15e]/10 text-[#22a15e]">
                            <x-filament::icon icon="heroicon-o-beaker" class="size-6" />
                        </span>
                        <div>
                            <h2>InvestigaLab</h2>
                            <p>Ideas, evaluaciones y proyectos de investigación.</p>
                        </div>
                    </div>
                    <a href="{{ $investiga['url'] }}" class="damian-panel__link">Consultar área</a>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <article class="damian-supervisor-stat">
                        <span class="damian-supervisor-stat__icon bg-[#1bb1e3]/10 text-[#1bb1e3]">
                            <x-filament::icon icon="heroicon-o-light-bulb" class="size-6" />
                        </span>
                        <div><p>Ideas</p><strong>{{ $investiga['ideas'] }}</strong><span>Registradas</span></div>
                    </article>
                    <article class="damian-supervisor-stat">
                        <span class="damian-supervisor-stat__icon bg-amber-500/10 text-amber-500">
                            <x-filament::icon icon="heroicon-o-magnifying-glass" class="size-6" />
                        </span>
                        <div><p>Evaluación</p><strong>{{ $investiga['evaluacion'] }}</strong><span>En revisión</span></div>
                    </article>
                    <article class="damian-supervisor-stat">
                        <span class="damian-supervisor-stat__icon bg-[#22a15e]/10 text-[#22a15e]">
                            <x-filament::icon icon="heroicon-o-beaker" class="size-6" />
                        </span>
                        <div><p>Proyectos activos</p><strong>{{ $investiga['activos'] }}</strong><span>En desarrollo</span></div>
                    </article>
                    <article class="damian-supervisor-stat">
                        <span class="damian-supervisor-stat__icon bg-[#1a4e5c]/10 text-[#1a4e5c] dark:text-[#31bae4]">
                            <x-filament::icon icon="heroicon-o-rectangle-stack" class="size-6" />
                        </span>
                        <div><p>Total</p><strong>{{ $investiga['total'] }}</strong><span>Solicitudes</span></div>
                    </article>
                </div>
            </article>

            <article class="damian-panel border-t-2 border-t-[#1a4e5c]">
                <div class="damian-panel__header">
                    <div class="flex items-center gap-3">
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-[#1a4e5c]/10 text-[#1a4e5c] dark:text-[#31bae4]">
                            <x-filament::icon icon="heroicon-o-cpu-chip" class="size-6" />
                        </span>
                        <div>
                            <h2>Damian Automation</h2>
                            <p>Automatización, máquinas, PLC, software e IoT.</p>
                        </div>
                    </div>
                    <a href="{{ $automation['url'] }}" class="damian-panel__link">Consultar área</a>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <article class="damian-supervisor-stat">
                        <span class="damian-supervisor-stat__icon bg-[#1bb1e3]/10 text-[#1bb1e3]">
                            <x-filament::icon icon="heroicon-o-inbox-arrow-down" class="size-6" />
                        </span>
                        <div><p>Solicitudes</p><strong>{{ $automation['solicitudes'] }}</strong><span>Por evaluar</span></div>
                    </article>
                    <article class="damian-supervisor-stat">
                        <span class="damian-supervisor-stat__icon bg-amber-500/10 text-amber-500">
                            <x-filament::icon icon="heroicon-o-banknotes" class="size-6" />
                        </span>
                        <div><p>Cotizaciones</p><strong>{{ $automation['cotizaciones'] }}</strong><span>En decisión</span></div>
                    </article>
                    <article class="damian-supervisor-stat">
                        <span class="damian-supervisor-stat__icon bg-[#22a15e]/10 text-[#22a15e]">
                            <x-filament::icon icon="heroicon-o-wrench-screwdriver" class="size-6" />
                        </span>
                        <div><p>Proyectos activos</p><strong>{{ $automation['activos'] }}</strong><span>En desarrollo</span></div>
                    </article>
                    <article class="damian-supervisor-stat">
                        <span class="damian-supervisor-stat__icon bg-[#1a4e5c]/10 text-[#1a4e5c] dark:text-[#31bae4]">
                            <x-filament::icon icon="heroicon-o-rectangle-stack" class="size-6" />
                        </span>
                        <div><p>Total</p><strong>{{ $automation['total'] }}</strong><span>Expedientes</span></div>
                    </article>
                </div>
            </article>
        </section>

        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold tracking-[-.025em] text-[#152036] dark:text-[#f3f3f3]">
                    Seguimiento DAMI 3D
                </h2>
                <p class="mt-1 text-sm text-[#65738a] dark:text-[#99a5b5]">
                    Pedidos, alertas y disponibilidad operativa del área.
                </p>
            </div>
            <a href="{{ $urls['orders'] }}" class="damian-panel__link">Ver DAMI 3D</a>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(20rem,.55fr)]">
            <section class="damian-panel min-w-0">
                <div class="damian-panel__header">
                    <div>
                        <h2>Pedidos recientes</h2>
                    </div>
                    <a href="{{ $urls['filteredOrders'] }}" class="damian-panel__link">
                        {{ filled($this->orderSearch) ? 'Ver más resultados' : 'Ver todos' }}
                    </a>
                </div>

                <div class="damian-recent-search">
                    <x-filament::icon icon="heroicon-o-magnifying-glass" class="size-5" />
                    <input
                        type="search"
                        wire:model.live.debounce.350ms="orderSearch"
                        placeholder="Buscar por cliente, orden o descripción..."
                        aria-label="Buscar pedidos recientes"
                    >
                    <span wire:loading wire:target="orderSearch">Buscando...</span>
                </div>

                @if ($recentOrders->isEmpty())
                    <div class="damian-empty">
                        <x-filament::icon icon="heroicon-o-cube-transparent" class="size-8 text-[#1bb1e3]" />
                        <div>
                            <p class="font-semibold">{{ filled($this->orderSearch) ? 'No encontramos coincidencias' : 'Aún no hay pedidos activos' }}</p>
                            <p class="mt-1 text-sm text-[#65738a] dark:text-[#99a5b5]">
                                {{ filled($this->orderSearch) ? 'Prueba con otro cliente o palabra de la descripción.' : 'El resumen aparecerá cuando exista un pedido por atender.' }}
                            </p>
                        </div>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="damian-table">
                            <thead>
                                <tr>
                                    <th>Orden</th>
                                    <th>Cliente</th>
                                    <th>Descripción</th>
                                    <th>Avance</th>
                                    <th>Entrega</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentOrders as $order)
                                    <tr>
                                        <td class="font-semibold text-[#168fdd] dark:text-[#31bae4]">{{ $order->order_number }}</td>
                                        <td>{{ $order->client_name }}</td>
                                        <td class="max-w-64">
                                            <span class="line-clamp-2">{{ $order->description }}</span>
                                        </td>
                                        <td>{{ match ($order->status) {
                                            'pending' => 'Pendiente',
                                            'in_progress' => 'En proceso',
                                            'completed' => 'Completado',
                                            default => $order->status,
                                        } }}</td>
                                        <td>{{ $order->delivery_date?->format('d/m/Y') ?: 'Por definir' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="damian-panel">
                <div class="damian-panel__header">
                    <div>
                        <h2>Avance de pedidos</h2>
                    </div>
                    <a href="{{ $urls['orders'] }}" class="damian-panel__link">Ver detalle</a>
                </div>

                <div class="damian-progress-compact">
                    @foreach ($progress as $stage)
                        <article>
                            <span @class([
                                'size-2.5 shrink-0 rounded-full',
                                'bg-[#1bb1e3]' => $stage['tone'] === 'blue',
                                'bg-[#22a15e]' => $stage['tone'] === 'green',
                                'bg-[#1a4e5c] dark:bg-[#31bae4]' => ! in_array($stage['tone'], ['blue', 'green'], true),
                            ])></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-3">
                                    <p>{{ $stage['label'] }}</p>
                                    <strong>{{ $stage['count'] }}</strong>
                                </div>
                                <span>{{ $stage['detail'] }}</span>
                                <div class="mt-2 h-1 overflow-hidden rounded-full bg-[#eaeceb] dark:bg-white/10">
                                    <div @class([
                                        'h-full w-full rounded-full opacity-75',
                                        'bg-[#1bb1e3]' => $stage['tone'] === 'blue',
                                        'bg-[#22a15e]' => $stage['tone'] === 'green',
                                        'bg-[#1a4e5c] dark:bg-[#31bae4]' => ! in_array($stage['tone'], ['blue', 'green'], true),
                                    ])></div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(18rem,.62fr)_minmax(0,1.38fr)]">
            <section class="damian-panel">
                <div class="damian-panel__header">
                    <div>
                        <h2>Atención requerida</h2>
                    </div>
                </div>

                @if ($alerts->isEmpty())
                    <div class="damian-empty damian-empty--compact">
                        <span class="grid size-10 place-items-center rounded-xl bg-[#22a15e]/10 text-[#22a15e]">
                            <x-filament::icon icon="heroicon-o-check-circle" class="size-5" />
                        </span>
                        <div>
                            <p class="font-semibold">Todo marcha con normalidad</p>
                            <p class="mt-1 text-sm text-[#65738a] dark:text-[#99a5b5]">No hay excepciones que revisar.</p>
                        </div>
                    </div>
                @else
                    <div class="space-y-2.5">
                        @foreach ($alerts as $alert)
                            <div class="damian-alert damian-alert--{{ $alert['tone'] }}">
                                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="size-5 shrink-0" />
                                <div>
                                    <p class="text-sm font-semibold">{{ $alert['label'] }}</p>
                                    <p class="mt-1 text-xs opacity-75">{{ $alert['detail'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="damian-panel">
                <div class="damian-panel__header">
                    <div>
                        <h2>Disponibilidad de impresoras</h2>
                    </div>
                    <a href="{{ $urls['printers'] }}" class="damian-panel__link">Gestionar impresoras</a>
                </div>

                <div class="grid gap-3 md:grid-cols-2 2xl:grid-cols-3">
                    @forelse ($printers as $printer)
                        <article class="damian-printer">
                        <div class="flex items-start justify-between gap-3">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-[#1bb1e3]/10 text-[#168fdd] dark:text-[#31bae4]">
                                <x-filament::icon icon="heroicon-o-printer" class="size-5" />
                            </span>
                            <span @class([
                                'rounded-lg px-2 py-1 text-[.68rem] font-semibold',
                                'text-[#22a15e] bg-[#22a15e]/10' => $printer->status === 'available',
                                'text-[#168fdd] bg-[#1bb1e3]/10 dark:text-[#31bae4]' => $printer->status === 'in_use',
                                'text-amber-600 bg-amber-500/10 dark:text-amber-400' => $printer->status === 'maintenance',
                                'text-red-600 bg-red-500/10 dark:text-red-400' => $printer->status === 'out_of_service',
                            ])>
                                {{ match ($printer->status) {
                                    'available' => 'Disponible',
                                    'in_use' => 'En uso',
                                    'maintenance' => 'Mantenimiento',
                                    'out_of_service' => 'Fuera de servicio',
                                    default => $printer->status,
                                } }}
                            </span>
                        </div>
                        <p class="mt-4 font-semibold">{{ $printer->name }}</p>
                        <p class="mt-1 flex items-center gap-1.5 text-xs text-[#65738a] dark:text-[#99a5b5]">
                            <x-filament::icon icon="heroicon-o-map-pin" class="size-3.5" />
                            {{ $printer->location }}
                        </p>
                        </article>
                    @empty
                        <div class="damian-empty md:col-span-2 2xl:col-span-3">
                            <x-filament::icon icon="heroicon-o-printer" class="size-8 text-[#1bb1e3]" />
                            <p class="font-semibold">No hay impresoras registradas.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
        @endif
    </div>
</x-filament-panels::page>
