@php
    $proveedor = $this->getRecord()->loadMissing(['categorias', 'marcas']);
    $estado = \App\Filament\Resources\ProveedoresServicioTecnico\Schemas\FormularioProveedorServicioTecnico::estados()[$proveedor->estado] ?? $proveedor->estado;
@endphp

<x-filament-panels::page>
    <div class="damian-technical-supplier-detail space-y-6">
        <section class="damian-technical-supplier-profile">
            <div class="damian-technical-supplier-profile__identity">
                <div class="damian-technical-supplier-profile__icon">
                    <x-filament::icon icon="heroicon-o-wrench-screwdriver" class="h-7 w-7" />
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="damian-status">{{ $estado }}</span>
                        @if($proveedor->principal)<span class="damian-status text-[#22a15e]">Proveedor principal</span>@endif
                        <span class="text-xs font-semibold uppercase tracking-[.08em] text-[#65738a] dark:text-[#99a5b5]">{{ $proveedor->codigo }}</span>
                    </div>
                    <h2>{{ $proveedor->nombre_comercial ?: $proveedor->razon_social }}</h2>
                    <p>{{ $proveedor->razon_social }} · {{ strtoupper($proveedor->tipo_documento ?: 'Documento') }} {{ $proveedor->numero_documento ?: 'no registrado' }}</p>
                </div>
            </div>
            <div class="damian-technical-supplier-profile__metrics">
                <div><span>Categorías</span><strong>{{ $proveedor->categorias->count() }}</strong></div>
                <div><span>Marcas</span><strong>{{ $proveedor->marcas->count() }}</strong></div>
                <div><span>Entrega</span><strong>{{ $proveedor->entrega_promedio_dias !== null ? $proveedor->entrega_promedio_dias.' días' : 'Consultar' }}</strong></div>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="damian-order-section">
                <div class="damian-order-section__heading"><div><h3>Contacto</h3><p>Datos útiles para solicitar atención o repuestos.</p></div></div>
                <dl class="damian-order-data">
                    <div><dt>Persona de contacto</dt><dd>{{ $proveedor->contacto_nombre ?: 'No registrada' }}</dd></div>
                    <div><dt>WhatsApp</dt><dd>{{ $proveedor->whatsapp ?: 'No registrado' }}</dd></div>
                    <div><dt>Teléfono</dt><dd>{{ $proveedor->telefono ?: 'No registrado' }}</dd></div>
                    <div><dt>Correo</dt><dd>{{ $proveedor->correo ?: 'No registrado' }}</dd></div>
                    <div class="sm:col-span-2"><dt>Dirección</dt><dd>{{ $proveedor->direccion ?: 'No registrada' }}</dd></div>
                </dl>
            </section>

            <section class="damian-order-section">
                <div class="damian-order-section__heading"><div><h3>Abastecimiento</h3><p>Categorías, marcas y productos que maneja.</p></div></div>
                <div class="damian-technical-supplier-groups">
                    <div>
                        <span>Categorías</span>
                        <div>@forelse($proveedor->categorias as $categoria)<span>{{ $categoria->nombre }}</span>@empty<small>Sin categorías</small>@endforelse</div>
                    </div>
                    <div>
                        <span>Marcas</span>
                        <div>@forelse($proveedor->marcas as $marca)<span>{{ $marca->nombre }}</span>@empty<small>Sin marcas</small>@endforelse</div>
                    </div>
                    <div>
                        <span>Productos principales</span>
                        <p>{{ $proveedor->productos_principales ?: 'No se registraron productos específicos.' }}</p>
                    </div>
                </div>
            </section>
        </div>

        <section class="damian-order-section">
            <div class="damian-order-section__heading"><div><h3>Condiciones</h3><p>Resumen rápido para futuras compras.</p></div></div>
            <dl class="damian-technical-supplier-conditions">
                <div><dt>Forma de pago</dt><dd>{{ $proveedor->forma_pago ? str($proveedor->forma_pago)->title() : 'Por consultar' }}</dd></div>
                <div><dt>Factura</dt><dd>{{ $proveedor->emite_factura ? 'Sí emite' : 'No confirmado' }}</dd></div>
                <div><dt>Entrega promedio</dt><dd>{{ $proveedor->entrega_promedio_dias !== null ? $proveedor->entrega_promedio_dias.' días' : 'Por consultar' }}</dd></div>
                <div><dt>Notas internas</dt><dd>{{ $proveedor->notas ?: 'Sin notas adicionales.' }}</dd></div>
            </dl>
        </section>
    </div>
</x-filament-panels::page>
