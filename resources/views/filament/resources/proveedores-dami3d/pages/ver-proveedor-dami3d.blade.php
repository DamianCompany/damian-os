@php
    $proveedor = $this->getRecord()->loadMissing(['categorias','marcas','productos.categoria','productos.marca','evaluaciones','incidencias','documentos','actividad']);
    $estado = \App\Filament\Resources\ProveedoresDami3d\Tables\TablaProveedoresDami3d::estados()[$proveedor->estado] ?? $proveedor->estado;
    $documentosVisibles = auth()->user()?->role === 'gerencia' ? $proveedor->documentos : $proveedor->documentos->where('tipo', '!=', 'datos_bancarios');
@endphp
<x-filament-panels::page>
    <div class="space-y-6">
        @if (in_array($proveedor->estado, ['suspendido','bloqueado'], true))
            <div class="rounded-2xl border border-red-500/30 bg-red-500/10 p-4 text-sm font-semibold text-red-600 dark:text-red-300">Este proveedor está {{ strtolower($estado) }}. {{ $proveedor->motivo_estado }}</div>
        @endif

        <section class="damian-order-hero">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2"><span class="damian-status">{{ $estado }}</span>@if($proveedor->principal)<span class="damian-status text-[#22a15e]">Proveedor principal</span>@endif</div>
                    <h2 class="mt-3 text-2xl font-bold tracking-[-.035em] text-[#152036] dark:text-[#f3f3f3]">{{ $proveedor->nombre_comercial ?: $proveedor->razon_social }}</h2>
                    <p class="mt-2 text-sm text-[#65738a] dark:text-[#a5aebd]">{{ $proveedor->razon_social }} · {{ strtoupper($proveedor->tipo_documento ?: 'Documento') }} {{ $proveedor->numero_documento ?: 'no registrado' }}</p>
                </div>
                <div class="rounded-2xl border border-[#1bb1e3]/15 px-6 py-4 text-center dark:border-white/10"><span class="text-xs text-[#65738a] dark:text-[#99a5b5]">Calificación</span><strong class="mt-1 block text-2xl text-[#22a15e]">{{ number_format((float)$proveedor->calificacion,1) }} / 5</strong></div>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[.75fr_1.25fr]">
            <section class="damian-order-section">
                <div class="damian-order-section__heading"><div><h3>Información general</h3><p>Contacto y condiciones vigentes.</p></div></div>
                <dl class="damian-order-data">
                    <div><dt>Contacto</dt><dd>{{ $proveedor->contacto_nombre ?: 'No registrado' }}</dd></div>
                    <div><dt>WhatsApp</dt><dd>{{ $proveedor->whatsapp ?: 'No registrado' }}</dd></div>
                    <div><dt>Correo</dt><dd>{{ $proveedor->correo_ventas ?: 'No registrado' }}</dd></div>
                    <div><dt>Ubicación</dt><dd>{{ collect([$proveedor->distrito,$proveedor->provincia,$proveedor->departamento])->filter()->implode(', ') ?: 'No registrada' }}</dd></div>
                    <div><dt>Pago</dt><dd>{{ $proveedor->condicion_pago ? str($proveedor->condicion_pago)->replace('_',' ')->title() : 'Por consultar' }}</dd></div>
                    <div><dt>Entrega promedio</dt><dd>{{ $proveedor->entrega_promedio_dias !== null ? $proveedor->entrega_promedio_dias.' días' : 'Por consultar' }}</dd></div>
                </dl>
                <div class="mt-5 flex flex-wrap gap-2">@foreach($proveedor->categorias as $categoria)<span class="rounded-full bg-[#1bb1e3]/10 px-3 py-1 text-xs font-semibold text-[#168fdd] dark:text-[#31bae4]">{{ $categoria->nombre }}</span>@endforeach</div>
            </section>

            <section class="damian-order-section">
                <div class="damian-order-section__heading"><div><h3>Productos y precios</h3><p>Valores referenciales; cada cambio conserva historial.</p></div></div>
                <div class="mt-5 overflow-x-auto">
                    <table class="w-full min-w-[42rem] text-left text-sm"><thead class="text-xs uppercase text-[#65738a] dark:text-[#99a5b5]"><tr><th class="pb-3">Producto</th><th>Marca</th><th>Presentación</th><th>Precio</th><th>Disponibilidad</th><th>Actualizado</th></tr></thead><tbody class="divide-y divide-[#1bb1e3]/10 dark:divide-white/10">
                    @forelse($proveedor->productos as $producto)<tr><td class="py-4 font-semibold text-[#152036] dark:text-[#f3f3f3]">{{ $producto->nombre }}<small class="block font-normal text-[#65738a] dark:text-[#99a5b5]">{{ $producto->categoria?->nombre }}</small></td><td>{{ $producto->marca?->nombre ?: 'Sin marca' }}</td><td>{{ $producto->presentacion ?: '—' }}</td><td class="font-semibold text-[#22a15e]">{{ $producto->moneda==='USD'?'US$':'S/' }} {{ number_format((float)$producto->precio_referencial,2) }}</td><td>{{ ucfirst(str_replace('_',' ',$producto->disponibilidad)) }}</td><td>{{ $producto->precio_actualizado_en?->format('d/m/Y') }}</td></tr>
                    @empty<tr><td colspan="6" class="py-8 text-center text-[#65738a] dark:text-[#99a5b5]">Todavía no hay productos registrados.</td></tr>@endforelse
                    </tbody></table>
                </div>
            </section>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="damian-order-section"><div class="damian-order-section__heading"><div><h3>Evaluaciones</h3><p>Desempeño registrado.</p></div></div><div class="mt-4 space-y-3">@forelse($proveedor->evaluaciones->sortByDesc('created_at')->take(4) as $e)<div class="rounded-xl border border-[#1bb1e3]/10 p-3"><strong class="text-[#22a15e]">{{ number_format((float)$e->promedio,1) }} / 5</strong><p class="mt-1 text-sm text-[#65738a] dark:text-[#99a5b5]">{{ $e->comentario ?: 'Sin comentario' }}</p></div>@empty<p class="text-sm text-[#65738a] dark:text-[#99a5b5]">Sin evaluaciones.</p>@endforelse</div></section>
            <section class="damian-order-section"><div class="damian-order-section__heading"><div><h3>Incidencias</h3><p>Problemas y seguimiento.</p></div></div><div class="mt-4 space-y-3">@forelse($proveedor->incidencias->sortByDesc('fecha')->take(4) as $i)<div class="rounded-xl border border-[#1bb1e3]/10 p-3"><div class="flex justify-between gap-3"><strong>{{ str($i->tipo)->replace('_',' ')->title() }}</strong><span class="text-xs">{{ str($i->estado)->replace('_',' ')->title() }}</span></div><p class="mt-1 text-sm text-[#65738a] dark:text-[#99a5b5]">{{ $i->descripcion }}</p></div>@empty<p class="text-sm text-[#65738a] dark:text-[#99a5b5]">Sin incidencias.</p>@endforelse</div></section>
            <section class="damian-order-section"><div class="damian-order-section__heading"><div><h3>Documentos</h3><p>Catálogos y listas de precios.</p></div></div><div class="mt-4 space-y-3">@forelse($documentosVisibles as $d)@if($d->archivo_drive_url)<a class="damian-order-file" href="{{ $d->archivo_drive_url }}" target="_blank"><span>{{ $d->nombre_original }}</span><small>{{ str($d->tipo)->replace('_',' ')->title() }}</small></a>@else<div class="damian-order-file"><span>{{ $d->nombre_original }}</span><small>Pendiente de Drive</small></div>@endif @empty<p class="text-sm text-[#65738a] dark:text-[#99a5b5]">Sin documentos.</p>@endforelse</div></section>
        </div>

        <section class="damian-order-section"><div class="damian-order-section__heading"><div><h3>Actividad reciente</h3><p>Trazabilidad de cambios importantes.</p></div></div><div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">@foreach($proveedor->actividad->sortByDesc('created_at')->take(6) as $a)<div class="rounded-xl border border-[#1bb1e3]/10 p-3"><strong class="text-sm">{{ $a->accion }}</strong><p class="mt-1 text-xs text-[#65738a] dark:text-[#99a5b5]">{{ $a->detalle }} · {{ $a->created_at?->format('d/m/Y H:i') }}</p></div>@endforeach</div></section>
    </div>
</x-filament-panels::page>
