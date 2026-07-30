@php
    use App\Filament\Resources\OrdenesServicioTecnico\Tables\TablaOrdenesServicioTecnico;

    $orden = $this->getRecord()->loadMissing(['archivos', 'historialEstados.autor', 'responsable']);
    $carpetas = $orden->carpetas_drive ?? [];
    $etapas = [
        ['clave' => 'ingreso', 'numero' => '01', 'nombre' => 'Ingreso', 'descripcion' => 'Cliente, equipo, falla, accesorios y fotografías.', 'icono' => 'heroicon-o-inbox-arrow-down', 'completa' => true, 'resumen' => $orden->ubicacion_fisica],
        ['clave' => 'diagnostico', 'numero' => '02', 'nombre' => 'Diagnóstico y cotización', 'descripcion' => 'Falla encontrada, solución, repuestos y precio.', 'icono' => 'heroicon-o-magnifying-glass', 'completa' => filled($orden->diagnostico) && filled($orden->precio_cotizado), 'resumen' => $orden->precio_cotizado ? 'S/ '.number_format((float) $orden->precio_cotizado, 2) : 'Pendiente'],
        ['clave' => 'reparacion', 'numero' => '03', 'nombre' => $orden->tipo_atencion === 'mantenimiento' ? 'Mantenimiento' : 'Reparación', 'descripcion' => 'Trabajo, tiempo real y materiales utilizados.', 'icono' => 'heroicon-o-wrench', 'completa' => filled($orden->trabajo_finalizado_en), 'resumen' => $orden->tiempo_real_minutos ? $orden->tiempo_real_minutos.' minutos' : 'Sin finalizar'],
        ['clave' => 'entrega', 'numero' => '04', 'nombre' => 'Prueba y entrega', 'descripcion' => 'Validación, recojo, pago, garantía o retorno.', 'icono' => 'heroicon-o-check-badge', 'completa' => filled($orden->entregado_en), 'resumen' => $orden->resultado_prueba ? ucfirst(str_replace('_', ' ', $orden->resultado_prueba)) : 'Sin prueba'],
    ];
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <section class="damian-order-hero">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="text-sm font-semibold text-[#1bb1e3]">{{ TablaOrdenesServicioTecnico::etiquetaEstado($orden->estado) }}</span>
                    <h2 class="mt-2 text-2xl font-bold tracking-[-.035em] text-[#152036] dark:text-[#f3f3f3]">
                        {{ TablaOrdenesServicioTecnico::etiquetaEquipo($orden->tipo_equipo) }}
                        @if ($orden->marca || $orden->modelo) · {{ trim("{$orden->marca} {$orden->modelo}") }} @endif
                    </h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-[#65738a] dark:text-[#a5aebd]">{{ $orden->falla_reportada }}</p>
                </div>
                <div class="w-full max-w-sm">
                    <div class="flex items-center justify-between text-sm"><span class="font-semibold text-[#152036] dark:text-[#f3f3f3]">Avance de la orden</span><strong class="text-[#22a15e]">{{ $orden->avance }}%</strong></div>
                    <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-[#eaeceb] dark:bg-[#152036]"><div class="h-full rounded-full bg-gradient-to-r from-[#1bb1e3] to-[#22a15e]" style="width: {{ $orden->avance }}%"></div></div>
                </div>
            </div>
        </section>

        <section class="damian-order-section">
            <div class="damian-order-section__heading">
                <div><h3>Ruta de la orden</h3><p>Cuatro pasos claros desde recepción hasta entrega.</p></div>
                @if ($orden->carpeta_drive_url)<a class="damian-panel__link" href="{{ $orden->carpeta_drive_url }}" target="_blank" rel="noopener">Abrir expediente en Drive</a>@endif
            </div>
            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($etapas as $etapa)
                    <article class="flex min-h-48 flex-col rounded-2xl border p-5 {{ $etapa['completa'] ? 'border-[#22a15e]/30 bg-[#22a15e]/5 dark:bg-[#22a15e]/10' : 'border-[#1bb1e3]/15 bg-white dark:border-white/10 dark:bg-[#182b49]' }}">
                        <div class="flex items-center justify-between"><span class="text-xs font-bold tracking-[.12em] text-[#1bb1e3]">{{ $etapa['numero'] }}</span><span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $etapa['completa'] ? 'bg-[#22a15e]/15 text-[#22a15e]' : 'bg-[#1bb1e3]/10 text-[#1bb1e3]' }}"><x-filament::icon :icon="$etapa['completa'] ? 'heroicon-o-check' : $etapa['icono']" class="h-5 w-5" /></span></div>
                        <h4 class="mt-4 text-base font-bold text-[#152036] dark:text-[#f3f3f3]">{{ $etapa['nombre'] }}</h4>
                        <p class="mt-2 flex-1 text-sm leading-5 text-[#65738a] dark:text-[#a5aebd]">{{ $etapa['descripcion'] }}</p>
                        <div class="mt-4 border-t border-[#1bb1e3]/10 pt-3 text-xs font-semibold text-[#65738a] dark:border-white/10 dark:text-[#99a5b5]">{{ $etapa['resumen'] }}</div>
                        @if (isset($carpetas[$etapa['clave']]))<a href="https://drive.google.com/drive/folders/{{ $carpetas[$etapa['clave']] }}" target="_blank" rel="noopener" class="mt-3 text-xs font-bold text-[#1bb1e3]">Ver carpeta</a>@endif
                        @if ($etapa['clave'] === 'diagnostico' && $orden->cotizacion_drive_url)
                            <a href="{{ $orden->cotizacion_drive_url }}" target="_blank" rel="noopener" class="mt-2 text-xs font-bold text-[#22a15e]">Ver cotización archivada</a>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[1.2fr_.8fr]">
            <section class="damian-order-section">
                <div class="damian-order-section__heading"><div><h3>Cliente y equipo</h3><p>Información de recepción y seguimiento.</p></div></div>
                <dl class="damian-order-data">
                    <div><dt>Cliente</dt><dd>{{ $orden->cliente }}</dd></div>
                    <div><dt>Teléfono</dt><dd>{{ $orden->telefono }}</dd></div>
                    <div><dt>DNI / RUC</dt><dd>{{ $orden->documento_cliente ?: 'No registrado' }}</dd></div>
                    <div><dt>Tipo de atención</dt><dd>{{ $orden->tipo_atencion === 'mantenimiento' ? 'Mantenimiento' : 'Reparación' }}</dd></div>
                    <div>
                        <dt>Facturación</dt>
                        <dd class="{{ $orden->requiere_factura ? 'text-[#22a15e]' : '' }}">
                            {{ $orden->requiere_factura ? 'Desea factura' : 'No requiere factura' }}
                        </dd>
                    </div>
                    <div><dt>Número de serie</dt><dd>{{ $orden->numero_serie ?: 'No registrado' }}</dd></div>
                    <div><dt>Accesorios</dt><dd>{{ $orden->accesorios ?: 'Ninguno registrado' }}</dd></div>
                    <div><dt>Condición</dt><dd>{{ ucfirst($orden->condicion_visible) }}</dd></div>
                    <div><dt>Ubicación</dt><dd>{{ $orden->ubicacion_fisica }}</dd></div>
                    <div><dt>Entrega estimada</dt><dd>{{ $orden->fecha_entrega_estimada?->format('d/m/Y') ?? 'Por definir' }}</dd></div>
                    <div><dt>Responsable</dt><dd>{{ $orden->responsable?->name ?? 'Pendiente' }}</dd></div>
                </dl>
            </section>
            <section class="damian-order-section">
                <div class="damian-order-section__heading"><div><h3>Fotografías</h3><p>Evidencias vinculadas al expediente.</p></div></div>
                <div class="mt-5 space-y-3">
                    @forelse ($orden->archivos as $archivo)
                        @if ($archivo->archivo_drive_url)<a class="damian-order-file" href="{{ $archivo->archivo_drive_url }}" target="_blank" rel="noopener"><span>{{ $archivo->nombre_original }}</span><small>Google Drive</small></a>
                        @else<div class="damian-order-file"><span>{{ $archivo->nombre_original }}</span><small>Pendiente de sincronización</small></div>@endif
                    @empty<div class="damian-empty"><strong>Sin fotografías</strong><span>La orden puede continuar y añadir evidencia después.</span></div>@endforelse
                </div>
            </section>
        </div>
    </div>
</x-filament-panels::page>
