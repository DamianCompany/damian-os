<x-filament-panels::page>
    <div class="damian-dashboard space-y-6">
        <section class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold text-[#22a15e]">Vista general</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-[-.04em] text-[#152036] dark:text-[#f3f3f3]">
                    Hola, {{ auth()->user()->name }}
                </h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-[#65738a] dark:text-[#99a5b5]">
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

        <section class="damian-panel">
            <div class="damian-panel__header">
                <div>
                    <h2>Avance de pedidos</h2>
                    <p>El flujo operativo detallado se resume en tres etapas fáciles de consultar.</p>
                </div>
                <a href="{{ $urls['orders'] }}" class="damian-panel__link">Consultar trazabilidad</a>
            </div>

            <div class="grid gap-3 md:grid-cols-3">
                @foreach ($progress as $stage)
                    @php
                        $tone = match ($stage['tone']) {
                            'blue' => 'bg-[#1bb1e3]',
                            'green' => 'bg-[#22a15e]',
                            default => 'bg-[#1a4e5c] dark:bg-[#31bae4]',
                        };
                    @endphp
                    <article class="damian-stage">
                        <div class="flex items-center justify-between gap-4">
                            <span class="size-3 rounded-full {{ $tone }}"></span>
                            <span class="text-3xl font-semibold tracking-[-.04em]">{{ $stage['count'] }}</span>
                        </div>
                        <p class="mt-5 text-sm font-semibold">{{ $stage['label'] }}</p>
                        <p class="mt-1 text-xs text-[#65738a] dark:text-[#99a5b5]">{{ $stage['detail'] }}</p>
                        <div class="mt-4 h-1 overflow-hidden rounded-full bg-[#eaeceb] dark:bg-white/10">
                            <div class="h-full w-full rounded-full {{ $tone }} opacity-75"></div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <div class="grid gap-6 2xl:grid-cols-[minmax(0,1.35fr)_minmax(22rem,.65fr)]">
            <section class="damian-panel min-w-0">
                <div class="damian-panel__header">
                    <div>
                        <h2>Pedidos recientes</h2>
                        <p>Consulta rápida; la edición corresponde al supervisor DAMI 3D.</p>
                    </div>
                    <a href="{{ $urls['orders'] }}" class="damian-panel__link">Ver todos</a>
                </div>

                @if ($recentOrders->isEmpty())
                    <div class="damian-empty">
                        <x-filament::icon icon="heroicon-o-cube-transparent" class="size-8 text-[#1bb1e3]" />
                        <div>
                            <p class="font-semibold">Aún no hay pedidos registrados</p>
                            <p class="mt-1 text-sm text-[#65738a] dark:text-[#99a5b5]">El resumen aparecerá cuando el supervisor cree el primer pedido.</p>
                        </div>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="damian-table">
                            <thead>
                                <tr>
                                    <th>Orden</th>
                                    <th>Cliente</th>
                                    <th>Avance</th>
                                    <th>Entrega</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentOrders as $order)
                                    <tr>
                                        <td class="font-semibold text-[#168fdd] dark:text-[#31bae4]">{{ $order->order_number }}</td>
                                        <td>{{ $order->client_name }}</td>
                                        <td>{{ match ($order->status) {
                                            'new', 'draft', 'planned' => 'Pendiente',
                                            'in_progress', 'review', 'blocked' => 'En proceso',
                                            'ready', 'delivered' => 'Completado',
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
                        <h2>Atención requerida</h2>
                        <p>Solo excepciones importantes para Gerencia.</p>
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
        </div>

        <section class="damian-panel">
            <div class="damian-panel__header">
                <div>
                    <h2>Disponibilidad de impresoras</h2>
                    <p>Nombre, ubicación y estado actual. El responsable pertenece al pedido asignado.</p>
                </div>
                <a href="{{ $urls['printers'] }}" class="damian-panel__link">Gestionar impresoras</a>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @forelse ($printers as $printer)
                    @php
                        $printerStatus = match ($printer->status) {
                            'available' => ['label' => 'Disponible', 'class' => 'text-[#22a15e] bg-[#22a15e]/10'],
                            'in_use' => ['label' => 'En uso', 'class' => 'text-[#168fdd] bg-[#1bb1e3]/10 dark:text-[#31bae4]'],
                            'maintenance' => ['label' => 'Mantenimiento', 'class' => 'text-amber-600 bg-amber-500/10 dark:text-amber-400'],
                            'out_of_service' => ['label' => 'Fuera de servicio', 'class' => 'text-red-600 bg-red-500/10 dark:text-red-400'],
                            default => ['label' => $printer->status, 'class' => 'text-[#65738a] bg-[#99a5b5]/10'],
                        };
                    @endphp
                    <article class="damian-printer">
                        <div class="flex items-start justify-between gap-3">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-[#1bb1e3]/10 text-[#168fdd] dark:text-[#31bae4]">
                                <x-filament::icon icon="heroicon-o-printer" class="size-5" />
                            </span>
                            <span class="rounded-lg px-2 py-1 text-[.68rem] font-semibold {{ $printerStatus['class'] }}">{{ $printerStatus['label'] }}</span>
                        </div>
                        <p class="mt-4 font-semibold">{{ $printer->name }}</p>
                        <p class="mt-1 flex items-center gap-1.5 text-xs text-[#65738a] dark:text-[#99a5b5]">
                            <x-filament::icon icon="heroicon-o-map-pin" class="size-3.5" />
                            {{ $printer->location }}
                        </p>
                    </article>
                @empty
                    <div class="damian-empty sm:col-span-2 xl:col-span-4">
                        <x-filament::icon icon="heroicon-o-printer" class="size-8 text-[#1bb1e3]" />
                        <p class="font-semibold">No hay impresoras registradas.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-filament-panels::page>
