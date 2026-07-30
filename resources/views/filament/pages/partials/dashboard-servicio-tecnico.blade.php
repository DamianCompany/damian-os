<section class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <h1 class="text-3xl font-semibold tracking-[-.04em] text-[#152036] dark:text-[#f3f3f3]">Servicio Técnico</h1>
        <p class="mt-2 text-base text-[#65738a] dark:text-[#99a5b5]">
            Hola, <strong class="font-semibold text-[#168fdd] dark:text-[#31bae4]">{{ auth()->user()->name }}</strong>.
            Equipos recibidos, trabajo técnico y entregas en un solo lugar.
        </p>
    </div>
    <a href="{{ $urls['crearOrdenServicioTecnico'] }}" class="damian-dashboard__action damian-dashboard__action--primary">
        <x-filament::icon icon="heroicon-o-plus" class="size-5" />
        Recibir equipo
    </a>
</section>

<section class="grid gap-4 md:grid-cols-3" aria-label="Resumen de Servicio Técnico">
    @foreach ($servicioTecnicoResumen as $indicador)
        <article class="damian-supervisor-stat">
            <span @class([
                'damian-supervisor-stat__icon',
                'text-[#1bb1e3] bg-[#1bb1e3]/10' => $indicador['tone'] === 'blue',
                'text-[#1a4e5c] bg-[#1a4e5c]/10 dark:text-[#31bae4]' => $indicador['tone'] === 'teal',
                'text-[#22a15e] bg-[#22a15e]/10' => $indicador['tone'] === 'green',
            ])>
                <x-filament::icon :icon="$indicador['icon']" class="size-6" />
            </span>
            <div><p>{{ $indicador['label'] }}</p><strong>{{ $indicador['count'] }}</strong><span>{{ $indicador['detail'] }}</span></div>
        </article>
    @endforeach
</section>

<div class="grid gap-6 xl:grid-cols-[minmax(0,1.55fr)_minmax(18rem,.65fr)]">
    <section class="damian-panel min-w-0">
        <div class="damian-panel__header">
            <h2>Equipos en atención</h2>
            <a href="{{ $urls['ordenesServicioTecnico'] }}" class="damian-panel__link">Ver todos</a>
        </div>
        @forelse ($ordenesServicioRecientes as $orden)
            <article class="damian-work-row">
                <div class="min-w-0">
                    <a href="{{ \App\Filament\Resources\OrdenesServicioTecnico\OrdenServicioTecnicoResource::getUrl('view', ['record' => $orden]) }}" class="damian-work-row__order">{{ $orden->codigo }}</a>
                    <p class="damian-work-row__client">{{ $orden->cliente }}</p>
                </div>
                <p class="damian-work-row__description">{{ $orden->tipo_equipo }} · {{ $orden->falla_reportada }}</p>
                <span class="damian-status">{{ str($orden->estado)->replace('_', ' ')->title() }}</span>
                <a href="{{ \App\Filament\Resources\OrdenesServicioTecnico\OrdenServicioTecnicoResource::getUrl('view', ['record' => $orden]) }}" class="damian-work-row__action">
                    Ver ficha <x-filament::icon icon="heroicon-o-arrow-right" class="size-4" />
                </a>
            </article>
        @empty
            <div class="damian-empty">
                <x-filament::icon icon="heroicon-o-wrench-screwdriver" class="size-8 text-[#22a15e]" />
                <div><p class="font-semibold">No hay equipos pendientes</p><p class="mt-1 text-sm text-[#65738a] dark:text-[#99a5b5]">Los nuevos ingresos aparecerán aquí.</p></div>
            </div>
        @endforelse
    </section>

    <section class="damian-panel">
        <div class="damian-panel__header"><h2>Entregas próximas</h2></div>
        <div class="damian-deadlines">
            @forelse ($entregasServicioProximas as $orden)
                <a href="{{ \App\Filament\Resources\OrdenesServicioTecnico\OrdenServicioTecnicoResource::getUrl('view', ['record' => $orden]) }}">
                    <span class="damian-deadlines__icon"><x-filament::icon icon="heroicon-o-calendar-days" class="size-5" /></span>
                    <span class="min-w-0 flex-1"><strong>{{ $orden->tipo_equipo }}</strong><small>{{ $orden->cliente }}</small></span>
                    <span class="damian-deadlines__date"><strong>{{ $orden->fecha_entrega_estimada->format('d/m/Y') }}</strong></span>
                </a>
            @empty
                <div class="damian-empty damian-empty--compact"><p class="font-semibold">Sin entregas programadas</p></div>
            @endforelse
        </div>
    </section>
</div>
