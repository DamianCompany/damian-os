@php
    use App\Filament\Resources\SolicitudesAutomation\Tables\TablaSolicitudesAutomation;

    $solicitud = $this->getRecord()->loadMissing(['archivos', 'historialEstados.autor', 'responsable']);
    $carpetas = $solicitud->carpetas_drive ?? [];
    $etapas = [
        ['clave' => 'solicitud', 'numero' => '01', 'nombre' => 'Solicitud', 'descripcion' => 'Cliente, necesidad, ubicación y archivos.', 'icono' => 'heroicon-o-inbox-arrow-down', 'completa' => true, 'resumen' => $solicitud->requiere_visita ? 'Requiere visita' : 'Registro completo'],
        ['clave' => 'alcance', 'numero' => '02', 'nombre' => 'Alcance', 'descripcion' => 'Objetivo, límites, entregables y requisitos técnicos.', 'icono' => 'heroicon-o-document-text', 'completa' => filled($solicitud->objetivo) && filled($solicitud->criterios_aceptacion), 'resumen' => count($solicitud->entregables ?? []).' entregables'],
        ['clave' => 'cotizacion', 'numero' => '03', 'nombre' => 'Cotización', 'descripcion' => 'Factibilidad, actividades, costos, margen y fechas.', 'icono' => 'heroicon-o-banknotes', 'completa' => filled($solicitud->factibilidad) && filled($solicitud->precio_venta), 'resumen' => $solicitud->precio_venta ? 'S/ '.number_format((float) $solicitud->precio_venta, 2) : 'Por calcular'],
        ['clave' => 'proyecto', 'numero' => '04', 'nombre' => 'Proyecto', 'descripcion' => 'Aprobación, tareas, avances, versiones y cambios.', 'icono' => 'heroicon-o-wrench-screwdriver', 'completa' => filled($solicitud->fecha_aprobacion) && count($solicitud->tareas ?? []) > 0, 'resumen' => count($solicitud->tareas ?? []).' tareas'],
        ['clave' => 'pruebas', 'numero' => '05', 'nombre' => 'Pruebas', 'descripcion' => 'FAT, SAT, UAT, instalación y capacitación.', 'icono' => 'heroicon-o-clipboard-document-check', 'completa' => collect($solicitud->pruebas ?? [])->contains(fn ($prueba) => ($prueba['resultado'] ?? null) === 'conforme'), 'resumen' => count($solicitud->pruebas ?? []).' pruebas'],
        ['clave' => 'entrega', 'numero' => '06', 'nombre' => 'Entrega y soporte', 'descripcion' => 'Acta, garantía e incidencias posteriores.', 'icono' => 'heroicon-o-truck', 'completa' => filled($solicitud->fecha_entrega), 'resumen' => count($solicitud->incidencias_soporte ?? []).' incidencias'],
    ];
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <section class="damian-order-hero">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0">
                    <span class="text-sm font-semibold text-[#1bb1e3]">{{ TablaSolicitudesAutomation::etiquetaEstado($solicitud->estado) }}</span>
                    <h2 class="mt-2 text-2xl font-bold tracking-[-.035em] text-[#152036] dark:text-[#f3f3f3]">{{ $solicitud->titulo }}</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-[#65738a] dark:text-[#a5aebd]">{{ $solicitud->necesidad }}</p>
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
                <div><h3>Ruta de ingeniería</h3><p>Del requerimiento inicial a la entrega y soporte.</p></div>
                @if ($solicitud->carpeta_drive_url)
                    <a class="damian-panel__link" href="{{ $solicitud->carpeta_drive_url }}" target="_blank" rel="noopener">Abrir proyecto en Drive</a>
                @endif
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($etapas as $etapa)
                    <article class="flex min-h-48 flex-col rounded-2xl border p-5 {{ $etapa['completa'] ? 'border-[#22a15e]/30 bg-[#22a15e]/5 dark:bg-[#22a15e]/10' : 'border-[#1bb1e3]/15 bg-white dark:border-white/10 dark:bg-[#182b49]' }}">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold tracking-[.12em] text-[#1bb1e3]">{{ $etapa['numero'] }}</span>
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $etapa['completa'] ? 'bg-[#22a15e]/15 text-[#22a15e]' : 'bg-[#1bb1e3]/10 text-[#1bb1e3]' }}">
                                <x-filament::icon :icon="$etapa['completa'] ? 'heroicon-o-check' : $etapa['icono']" class="h-5 w-5" />
                            </span>
                        </div>
                        <h4 class="mt-4 text-base font-bold text-[#152036] dark:text-[#f3f3f3]">{{ $etapa['nombre'] }}</h4>
                        <p class="mt-2 flex-1 text-sm leading-5 text-[#65738a] dark:text-[#a5aebd]">{{ $etapa['descripcion'] }}</p>
                        <div class="mt-4 border-t border-[#1bb1e3]/10 pt-3 text-xs font-semibold text-[#65738a] dark:border-white/10 dark:text-[#99a5b5]">{{ $etapa['resumen'] }}</div>
                        @if (isset($carpetas[$etapa['clave']]))
                            <a href="https://drive.google.com/drive/folders/{{ $carpetas[$etapa['clave']] }}" target="_blank" rel="noopener" class="mt-3 text-xs font-bold text-[#1bb1e3]">Ver carpeta</a>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[1.2fr_.8fr]">
            <section class="damian-order-section">
                <div class="damian-order-section__heading"><div><h3>Información comercial y técnica</h3><p>Datos principales del expediente.</p></div></div>
                <dl class="damian-order-data">
                    <div><dt>Cliente</dt><dd>{{ $solicitud->cliente }}</dd></div>
                    <div><dt>Contacto</dt><dd>{{ $solicitud->contacto_nombre ?: 'Sin contacto' }}</dd></div>
                    <div><dt>Servicio</dt><dd>{{ TablaSolicitudesAutomation::etiquetaTipo($solicitud->tipo_servicio) }}</dd></div>
                    <div><dt>Responsable</dt><dd>{{ $solicitud->responsable?->name ?? 'Pendiente' }}</dd></div>
                    <div><dt>Ubicación</dt><dd>{{ $solicitud->ubicacion ?: 'Por definir' }}</dd></div>
                    <div><dt>Fecha requerida</dt><dd>{{ $solicitud->fecha_requerida?->format('d/m/Y') ?? 'Sin fecha' }}</dd></div>
                    <div><dt>Costo estimado</dt><dd>{{ $solicitud->costo_estimado ? 'S/ '.number_format((float) $solicitud->costo_estimado, 2) : 'Por calcular' }}</dd></div>
                    <div><dt>Precio de venta</dt><dd>{{ $solicitud->precio_venta ? 'S/ '.number_format((float) $solicitud->precio_venta, 2) : 'Por calcular' }}</dd></div>
                </dl>
            </section>

            <section class="damian-order-section">
                <div class="damian-order-section__heading"><div><h3>Archivos iniciales</h3><p>Fotos, planos y documentos del cliente.</p></div></div>
                <div class="mt-5 space-y-3">
                    @forelse ($solicitud->archivos as $archivo)
                        @if ($archivo->archivo_drive_url)
                            <a class="damian-order-file" href="{{ $archivo->archivo_drive_url }}" target="_blank" rel="noopener"><span>{{ $archivo->nombre_original }}</span><small>Google Drive</small></a>
                        @else
                            <div class="damian-order-file"><span>{{ $archivo->nombre_original }}</span><small>Pendiente de sincronización</small></div>
                        @endif
                    @empty
                        <div class="damian-empty"><strong>Sin archivos adjuntos</strong><span>Se puede continuar sin documentación inicial.</span></div>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="damian-order-section">
            <div class="damian-order-section__heading"><div><h3>Historial de trazabilidad</h3><p>Cambios de estado registrados automáticamente.</p></div></div>
            <div class="mt-5 space-y-3">
                @foreach ($solicitud->historialEstados->sortByDesc('created_at') as $evento)
                    <div class="flex items-center justify-between gap-4 rounded-xl border border-[#1bb1e3]/10 px-4 py-3 dark:border-white/10">
                        <div>
                            <strong class="text-sm text-[#152036] dark:text-[#f3f3f3]">{{ TablaSolicitudesAutomation::etiquetaEstado($evento->estado_nuevo) }}</strong>
                            <p class="mt-1 text-xs text-[#65738a] dark:text-[#99a5b5]">{{ $evento->nota ?? 'Estado actualizado' }} · {{ $evento->autor?->name ?? 'Sistema' }}</p>
                        </div>
                        <time class="text-xs text-[#65738a] dark:text-[#99a5b5]">{{ $evento->created_at?->format('d/m/Y H:i') }}</time>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-filament-panels::page>
