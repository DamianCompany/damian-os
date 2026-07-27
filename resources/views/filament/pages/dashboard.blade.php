<x-filament-panels::page>
    <div class="damian-dashboard space-y-6">
        @if ($isSupervisor)
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
                    Una lectura rápida del avance del área. Gerencia supervisa..
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

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Indicadores principales">
            <article class="damian-metric">
                <div class="damian-metric__icon bg-[#1bb1e3]/10 text-[#168fdd] dark:text-[#31bae4]">
                    <x-filament::icon icon="heroicon-o-clipboard-document-list" class="size-6" />
                </div>
                <div>
                    <p class="damian-metric__label">Pedidos activos</p>
                    <p class="damian-metric__value">{{ $activeOrders }}</p>
                    <p class="damian-metric__detail">Pendientes o en atención</p>
                </div>
            </article>

            <article class="damian-metric">
                <div class="damian-metric__icon bg-[#22a15e]/10 text-[#22a15e]">
                    <x-filament::icon icon="heroicon-o-chart-bar-square" class="size-6" />
                </div>
                <div>
                    <p class="damian-metric__label">Avance general</p>
                    <p class="damian-metric__value">{{ $completionRate }}%</p>
                    <p class="damian-metric__detail">Pedidos completados</p>
                </div>
            </article>

            <article class="damian-metric">
                <div class="damian-metric__icon bg-[#1a4e5c]/10 text-[#1a4e5c] dark:bg-[#31bae4]/10 dark:text-[#31bae4]">
                    <x-filament::icon icon="heroicon-o-printer" class="size-6" />
                </div>
                <div>
                    <p class="damian-metric__label">Impresoras disponibles</p>
                    <p class="damian-metric__value">{{ $availablePrinters }} / {{ $totalPrinters }}</p>
                    <p class="damian-metric__detail">Equipos listos para asignar</p>
                </div>
            </article>

            <article class="damian-metric">
                <div class="damian-metric__icon bg-amber-500/10 text-amber-600 dark:text-amber-400">
                    <x-filament::icon icon="heroicon-o-bell-alert" class="size-6" />
                </div>
                <div>
                    <p class="damian-metric__label">Requieren atención</p>
                    <p class="damian-metric__value">{{ $attentionCount }}</p>
                    <p class="damian-metric__detail">Tipos de alerta activos</p>
                </div>
            </article>
        </section>

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
