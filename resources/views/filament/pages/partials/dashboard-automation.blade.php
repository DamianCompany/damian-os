<section class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <h1 class="text-3xl font-semibold tracking-[-.04em] text-[#152036] dark:text-[#f3f3f3]">Damian Automation</h1>
        <p class="mt-2 text-base text-[#65738a] dark:text-[#99a5b5]">
            Hola, <strong class="font-semibold text-[#168fdd] dark:text-[#31bae4]">{{ auth()->user()->name }}</strong>.
            Revisa solicitudes, cotizaciones y proyectos de ingeniería.
        </p>
    </div>
    <a href="{{ $urls['crearSolicitudAutomation'] }}" class="damian-dashboard__action damian-dashboard__action--primary">
        <x-filament::icon icon="heroicon-o-plus" class="size-5" /> Nueva solicitud
    </a>
</section>

<section class="grid gap-4 md:grid-cols-3" aria-label="Resumen de Damian Automation">
    @foreach ($automationResumen as $indicador)
        <article class="damian-supervisor-stat">
            <span @class([
                'damian-supervisor-stat__icon',
                'text-[#1bb1e3] bg-[#1bb1e3]/10' => $indicador['tone'] === 'blue',
                'text-[#1a4e5c] bg-[#1a4e5c]/10 dark:text-[#31bae4]' => $indicador['tone'] === 'teal',
                'text-[#22a15e] bg-[#22a15e]/10' => $indicador['tone'] === 'green',
            ])><x-filament::icon :icon="$indicador['icon']" class="size-6" /></span>
            <div><p>{{ $indicador['label'] }}</p><strong>{{ $indicador['count'] }}</strong><span>{{ $indicador['detail'] }}</span></div>
        </article>
    @endforeach
</section>

<div class="grid gap-6 xl:grid-cols-[minmax(0,1.55fr)_minmax(18rem,.65fr)]">
    <section class="damian-panel min-w-0">
        <div class="damian-panel__header"><h2>Trabajo reciente</h2><a href="{{ $urls['solicitudesAutomation'] }}" class="damian-panel__link">Ver todos</a></div>
        @if ($proyectosAutomationRecientes->isEmpty())
            <div class="damian-empty">
                <x-filament::icon icon="heroicon-o-cpu-chip" class="size-8 text-[#1bb1e3]" />
                <div><p class="font-semibold">Aún no hay solicitudes</p><p class="mt-1 text-sm text-[#65738a] dark:text-[#99a5b5]">El primer proyecto aparecerá aquí.</p></div>
            </div>
        @else
            <div class="damian-work-list">
                @foreach ($proyectosAutomationRecientes as $proyecto)
                    <article class="damian-work-row">
                        <div class="min-w-0">
                            <a href="{{ \App\Filament\Resources\SolicitudesAutomation\SolicitudAutomationResource::getUrl('view', ['record' => $proyecto]) }}" class="damian-work-row__order">{{ $proyecto->codigo }}</a>
                            <p class="damian-work-row__client">{{ $proyecto->cliente }}</p>
                        </div>
                        <p class="damian-work-row__description">{{ $proyecto->titulo }}</p>
                        <div class="damian-work-row__delivery"><span>Avance</span><strong>{{ $proyecto->avance }}%</strong></div>
                        <span class="damian-status">{{ \App\Filament\Resources\SolicitudesAutomation\Tables\TablaSolicitudesAutomation::etiquetaEstado($proyecto->estado) }}</span>
                        <a href="{{ \App\Filament\Resources\SolicitudesAutomation\SolicitudAutomationResource::getUrl('view', ['record' => $proyecto]) }}" class="damian-work-row__action">Ver ficha <x-filament::icon icon="heroicon-o-arrow-right" class="size-4" /></a>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="damian-panel">
        <div class="damian-panel__header"><h2>Próximos hitos</h2></div>
        <div class="damian-deadlines">
            @forelse ($entregasAutomation as $proyecto)
                <a href="{{ \App\Filament\Resources\SolicitudesAutomation\SolicitudAutomationResource::getUrl('view', ['record' => $proyecto]) }}">
                    <span class="damian-deadlines__icon"><x-filament::icon icon="heroicon-o-calendar-days" class="size-5" /></span>
                    <span class="min-w-0 flex-1"><strong>{{ \Illuminate\Support\Str::limit($proyecto->titulo, 34) }}</strong><small>{{ $proyecto->cliente }}</small></span>
                    <span class="damian-deadlines__date"><strong>{{ $proyecto->fecha_fin_estimada->format('d/m/Y') }}</strong></span>
                </a>
            @empty
                <div class="damian-empty damian-empty--compact"><p class="font-semibold">Sin fechas próximas</p></div>
            @endforelse
        </div>
    </section>
</div>
