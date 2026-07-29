@php
    use App\Filament\Resources\SolicitudesInvestiga\Tables\TablaSolicitudesInvestiga;

    $solicitud = $this->getRecord()->loadMissing(['archivos', 'historialEstados.autor', 'responsable']);
    $carpetas = $solicitud->carpetas_drive ?? [];
    $etapas = [
        ['clave' => 'solicitud', 'numero' => '01', 'nombre' => 'Solicitud y definición', 'descripcion' => 'Problema, objetivos, alcance y criterios de éxito.', 'icono' => 'heroicon-o-light-bulb', 'completa' => filled($solicitud->objetivo_general) && filled($solicitud->criterios_exito), 'resumen' => filled($solicitud->objetivo_general) ? 'Objetivo definido' : 'Falta definir el objetivo'],
        ['clave' => 'planificacion', 'numero' => '02', 'nombre' => 'Planificación', 'descripcion' => 'Factibilidad, actividades, recursos, fechas y presupuesto.', 'icono' => 'heroicon-o-calendar-days', 'completa' => filled($solicitud->factibilidad) && count($solicitud->actividades ?? []) > 0, 'resumen' => count($solicitud->actividades ?? []).' actividades'],
        ['clave' => 'datos', 'numero' => '03', 'nombre' => 'Datos', 'descripcion' => 'Datasets, permisos, calidad, versiones y diccionario.', 'icono' => 'heroicon-o-circle-stack', 'completa' => count($solicitud->datasets ?? []) > 0, 'resumen' => count($solicitud->datasets ?? []).' datasets'],
        ['clave' => 'experimentos', 'numero' => '04', 'nombre' => 'Experimentos', 'descripcion' => 'Pruebas, prototipos, modelos, métricas y evidencias.', 'icono' => 'heroicon-o-beaker', 'completa' => count($solicitud->experimentos ?? []) > 0, 'resumen' => count($solicitud->experimentos ?? []).' registros'],
        ['clave' => 'resultados', 'numero' => '05', 'nombre' => 'Resultados y cierre', 'descripcion' => 'Análisis, entregables, propiedad, publicación y entrega.', 'icono' => 'heroicon-o-document-check', 'completa' => filled($solicitud->resultado_principal) && count($solicitud->entregables ?? []) > 0, 'resumen' => count($solicitud->entregables ?? []).' entregables'],
    ];
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <section class="damian-order-hero">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0">
                    <span class="text-sm font-semibold text-[#22a15e]">{{ TablaSolicitudesInvestiga::etiquetaEstado($solicitud->estado) }}</span>
                    <h2 class="mt-2 text-2xl font-bold tracking-[-.035em] text-[#152036] dark:text-[#f3f3f3]">{{ $solicitud->titulo }}</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-[#65738a] dark:text-[#a5aebd]">{{ $solicitud->problema_necesidad }}</p>
                </div>
                <div class="w-full max-w-sm">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-semibold text-[#152036] dark:text-[#f3f3f3]">Avance del proyecto</span>
                        <strong class="text-[#22a15e]">{{ $solicitud->avance }}%</strong>
                    </div>
                    <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-[#eaeceb] dark:bg-[#152036]">
                        <div class="h-full rounded-full bg-gradient-to-r from-[#1bb1e3] to-[#22a15e]" style="width: {{ $solicitud->avance }}%"></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="damian-order-section">
            <div class="damian-order-section__heading">
                <div>
                    <h3>Ruta del proyecto</h3>
                    <p>Cada bloque se completa progresivamente desde la misma ficha.</p>
                </div>
                @if ($solicitud->carpeta_drive_url)
                    <a class="damian-panel__link" href="{{ $solicitud->carpeta_drive_url }}" target="_blank" rel="noopener">Abrir proyecto en Drive</a>
                @endif
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                @foreach ($etapas as $etapa)
                    <article class="flex min-h-52 flex-col rounded-2xl border p-5 transition {{ $etapa['completa'] ? 'border-[#22a15e]/30 bg-[#22a15e]/5 dark:bg-[#22a15e]/10' : 'border-[#1bb1e3]/15 bg-white dark:border-white/10 dark:bg-[#182b49]' }}">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold tracking-[.12em] text-[#1bb1e3]">{{ $etapa['numero'] }}</span>
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl {{ $etapa['completa'] ? 'bg-[#22a15e]/15 text-[#22a15e]' : 'bg-[#1bb1e3]/10 text-[#1bb1e3]' }}">
                                <x-filament::icon :icon="$etapa['completa'] ? 'heroicon-o-check' : $etapa['icono']" class="h-5 w-5" />
                            </span>
                        </div>
                        <h4 class="mt-5 text-base font-bold text-[#152036] dark:text-[#f3f3f3]">{{ $etapa['nombre'] }}</h4>
                        <p class="mt-2 flex-1 text-sm leading-5 text-[#65738a] dark:text-[#a5aebd]">{{ $etapa['descripcion'] }}</p>
                        <div class="mt-4 border-t border-[#1bb1e3]/10 pt-3 text-xs font-semibold text-[#65738a] dark:border-white/10 dark:text-[#99a5b5]">{{ $etapa['resumen'] }}</div>
                        @if (isset($carpetas[$etapa['clave']]))
                            <a href="https://drive.google.com/drive/folders/{{ $carpetas[$etapa['clave']] }}" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-1 text-xs font-bold text-[#1bb1e3]">
                                Ver carpeta <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="h-3.5 w-3.5" />
                            </a>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[1.2fr_.8fr]">
            <section class="damian-order-section">
                <div class="damian-order-section__heading"><div><h3>Información principal</h3><p>Datos de referencia para todo el proyecto.</p></div></div>
                <dl class="damian-order-data">
                    <div><dt>Solicitante</dt><dd>{{ $solicitud->solicitante }}</dd></div>
                    <div><dt>Sector</dt><dd>{{ ucfirst($solicitud->sector) }}</dd></div>
                    <div><dt>Tipo</dt><dd>{{ TablaSolicitudesInvestiga::etiquetaTipo($solicitud->tipo_proyecto) }}</dd></div>
                    <div><dt>Resultado esperado</dt><dd>{{ $solicitud->resultado_esperado }}</dd></div>
                    <div><dt>Responsable</dt><dd>{{ $solicitud->responsable?->name ?? 'Pendiente' }}</dd></div>
                    <div><dt>Fecha requerida</dt><dd>{{ $solicitud->fecha_requerida?->format('d/m/Y') ?? 'Sin fecha definida' }}</dd></div>
                    <div><dt>Presupuesto estimado</dt><dd>{{ $solicitud->presupuesto_estimado !== null ? 'S/ '.number_format((float) $solicitud->presupuesto_estimado, 2) : 'Por calcular' }}</dd></div>
                    <div><dt>Confidencialidad</dt><dd>{{ ucfirst($solicitud->confidencialidad) }}</dd></div>
                </dl>
            </section>

            <section class="damian-order-section">
                <div class="damian-order-section__heading"><div><h3>Archivos iniciales</h3><p>Documentos recibidos al registrar la solicitud.</p></div></div>
                <div class="mt-5 space-y-3">
                    @forelse ($solicitud->archivos as $archivo)
                        @if ($archivo->archivo_drive_url)
                            <a class="damian-order-file" href="{{ $archivo->archivo_drive_url }}" target="_blank" rel="noopener"><span>{{ $archivo->nombre_original }}</span><small>Google Drive</small></a>
                        @else
                            <div class="damian-order-file"><span>{{ $archivo->nombre_original }}</span><small>Pendiente de sincronización</small></div>
                        @endif
                    @empty
                        <div class="damian-empty"><strong>Sin archivos adjuntos</strong><span>El proyecto puede continuar y añadir evidencia después.</span></div>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="damian-order-section">
            <div class="damian-order-section__heading"><div><h3>Historial de trazabilidad</h3><p>Los cambios de estado se registran automáticamente.</p></div></div>
            <div class="mt-5 space-y-3">
                @foreach ($solicitud->historialEstados->sortByDesc('created_at') as $evento)
                    <div class="flex items-center justify-between gap-4 rounded-xl border border-[#1bb1e3]/10 px-4 py-3 dark:border-white/10">
                        <div>
                            <strong class="text-sm text-[#152036] dark:text-[#f3f3f3]">{{ TablaSolicitudesInvestiga::etiquetaEstado($evento->estado_nuevo) }}</strong>
                            <p class="mt-1 text-xs text-[#65738a] dark:text-[#99a5b5]">{{ $evento->nota ?? 'Estado actualizado' }} · {{ $evento->autor?->name ?? 'Sistema' }}</p>
                        </div>
                        <time class="text-xs text-[#65738a] dark:text-[#99a5b5]">{{ $evento->created_at?->format('d/m/Y H:i') }}</time>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-filament-panels::page>
