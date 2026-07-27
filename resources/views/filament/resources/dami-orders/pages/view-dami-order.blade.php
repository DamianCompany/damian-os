<x-filament-panels::page>
    @php
        $order = $this->getRecord();
        $status = match ($order->status) {
            'pending' => ['label' => 'Pendiente', 'class' => 'bg-[#1bb1e3]/10 text-[#168fdd] dark:text-[#31bae4]'],
            'in_progress' => ['label' => 'En proceso', 'class' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400'],
            'completed' => ['label' => 'Completado', 'class' => 'bg-[#22a15e]/10 text-[#22a15e]'],
            default => ['label' => $order->status, 'class' => 'bg-gray-500/10 text-gray-600'],
        };
        $money = fn ($value) => 'S/ '.number_format((float) $value, 2);
    @endphp

    <div class="damian-order-detail space-y-5">
        <section class="damian-order-hero">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="rounded-lg px-2.5 py-1 text-xs font-semibold {{ $status['class'] }}">{{ $status['label'] }}</span>
                    <span class="text-xs text-[#65738a] dark:text-[#99a5b5]">Actualizado {{ $order->updated_at->diffForHumans() }}</span>
                </div>
                <h2 class="mt-4 text-2xl font-bold tracking-[-.04em] text-[#152036] dark:text-[#f3f3f3]">{{ $order->order_number }}</h2>
                <p class="mt-2 text-sm font-semibold text-[#334155] dark:text-[#dbe4ee]">{{ $order->client_name }}</p>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-[#65738a] dark:text-[#99a5b5]">{{ $order->description }}</p>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <div class="damian-order-kpi">
                    <span>Total del pedido</span>
                    <strong>{{ $money($order->total_price) }}</strong>
                </div>
                <div class="damian-order-kpi">
                    <span>Costo total</span>
                    <strong>{{ $money($order->total_cost) }}</strong>
                </div>
                <div class="damian-order-kpi damian-order-kpi--profit">
                    <span>Ganancia</span>
                    <strong>{{ $money($order->profit) }}</strong>
                </div>
            </div>
        </section>

        <div class="grid gap-5 xl:grid-cols-2">
            <section class="damian-order-section">
                <div class="damian-order-section__heading">
                    <span class="damian-order-section__icon bg-[#1bb1e3]/10 text-[#168fdd] dark:text-[#31bae4]">
                        <x-filament::icon icon="heroicon-o-user" class="size-5" />
                    </span>
                    <div>
                        <h3>Cliente y pedido</h3>
                        <p>Información principal registrada por el supervisor.</p>
                    </div>
                </div>
                <dl class="damian-order-data">
                    <div><dt>Cliente / Razón social</dt><dd>{{ $order->client_name }}</dd></div>
                    <div><dt>DNI / RUC</dt><dd>{{ $order->client_document }}</dd></div>
                    <div><dt>Cantidad</dt><dd>{{ $order->quantity }} unidades</dd></div>
                    <div><dt>Estado operativo</dt><dd>{{ $status['label'] }}</dd></div>
                </dl>
            </section>

            <section class="damian-order-section">
                <div class="damian-order-section__heading">
                    <span class="damian-order-section__icon bg-[#22a15e]/10 text-[#22a15e]">
                        <x-filament::icon icon="heroicon-o-calendar-days" class="size-5" />
                    </span>
                    <div>
                        <h3>Planificación y tiempos</h3>
                        <p>Fechas comprometidas y duración productiva.</p>
                    </div>
                </div>
                <div class="damian-order-timeline">
                    <div><span>Inicio</span><strong>{{ $order->start_date?->format('d/m/Y') }}</strong></div>
                    <i></i>
                    <div><span>Término</span><strong>{{ $order->end_date?->format('d/m/Y') }}</strong></div>
                    <i></i>
                    <div><span>Entrega</span><strong>{{ $order->delivery_date?->format('d/m/Y') }}</strong></div>
                </div>
                <dl class="damian-order-data mt-5">
                    <div><dt>Tiempo de impresión</dt><dd>{{ number_format((float) $order->print_hours, 2) }} h</dd></div>
                    <div><dt>Tiempo de postproceso</dt><dd>{{ $order->postprocess_hours !== null ? number_format((float) $order->postprocess_hours, 2).' h' : 'Pendiente de completar' }}</dd></div>
                </dl>
            </section>

            <section class="damian-order-section">
                <div class="damian-order-section__heading">
                    <span class="damian-order-section__icon bg-[#1a4e5c]/10 text-[#1a4e5c] dark:text-[#31bae4]">
                        <x-filament::icon icon="heroicon-o-cube" class="size-5" />
                    </span>
                    <div>
                        <h3>Producción y materiales</h3>
                        <p>Recursos asignados para elaborar el pedido.</p>
                    </div>
                </div>
                <dl class="damian-order-data">
                    <div><dt>Filamento utilizado</dt><dd>{{ number_format((float) $order->filament_grams, 2) }} g</dd></div>
                    <div><dt>Tipo de filamento</dt><dd>{{ $order->filament_type }}</dd></div>
                    <div><dt>Impresora</dt><dd>{{ $order->printer?->name ?? 'No disponible' }}</dd></div>
                    <div><dt>Ubicación</dt><dd>{{ $order->printer_location }}</dd></div>
                    <div><dt>Responsable</dt><dd>{{ $order->responsible_name }}</dd></div>
                </dl>
            </section>

            <section class="damian-order-section">
                <div class="damian-order-section__heading">
                    <span class="damian-order-section__icon bg-[#3caa83]/10 text-[#22a15e]">
                        <x-filament::icon icon="heroicon-o-banknotes" class="size-5" />
                    </span>
                    <div>
                        <h3>Costos, venta y pagos</h3>
                        <p>Desglose financiero completo de la orden.</p>
                    </div>
                </div>
                <dl class="damian-order-data damian-order-data--money">
                    <div><dt>Costo de filamento</dt><dd>{{ $money($order->filament_cost) }}</dd></div>
                    <div><dt>Electricidad</dt><dd>{{ $money($order->electricity_cost) }}</dd></div>
                    <div><dt>Mano de obra</dt><dd>{{ $money($order->labor_cost) }}</dd></div>
                    <div><dt>Costo total</dt><dd>{{ $money($order->total_cost) }}</dd></div>
                    <div><dt>Precio unitario</dt><dd>{{ $money($order->unit_sale_price) }}</dd></div>
                    <div><dt>Total del pedido</dt><dd>{{ $money($order->total_price) }}</dd></div>
                    <div><dt>Adelanto</dt><dd>{{ $money($order->advance) }}</dd></div>
                    <div><dt>Saldo pendiente</dt><dd>{{ $money($order->pending_balance) }}</dd></div>
                </dl>
            </section>
        </div>

        <section class="damian-order-section">
            <div class="damian-order-section__heading">
                <span class="damian-order-section__icon bg-[#1bb1e3]/10 text-[#168fdd] dark:text-[#31bae4]">
                    <x-filament::icon icon="heroicon-o-paper-clip" class="size-5" />
                </span>
                <div>
                    <h3>Archivos del pedido</h3>
                    <p>Referencias visuales y archivo recibido para producción.</p>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @forelse ($order->referenceFiles()->orderBy('id')->pluck('path') as $index => $image)
                    @php($imageUrl = $this->getFileUrl($image))
                    <a @if ($imageUrl) href="{{ $imageUrl }}" target="_blank" @endif class="damian-order-file">
                        <span class="damian-order-file__preview">
                            @if ($imageUrl)
                                <img src="{{ $imageUrl }}" alt="Referencia {{ $index + 1 }}">
                            @else
                                <x-filament::icon icon="heroicon-o-photo" class="size-7" />
                            @endif
                        </span>
                        <span>
                            <strong>Imagen referencial {{ $index + 1 }}</strong>
                            <small>{{ basename($image) }}</small>
                        </span>
                    </a>
                @empty
                    <div class="damian-order-file damian-order-file--empty">
                        <x-filament::icon icon="heroicon-o-photo" class="size-6" />
                        <span><strong>Sin imágenes</strong><small>No se adjuntaron referencias.</small></span>
                    </div>
                @endforelse

                @php($receivedFile = $order->files()->where('type', 'received')->value('path'))
                @php($receivedFileUrl = $this->getFileUrl($receivedFile))
                <a @if ($receivedFileUrl) href="{{ $receivedFileUrl }}" target="_blank" @endif class="damian-order-file">
                    <span class="damian-order-file__preview damian-order-file__preview--document">
                        <x-filament::icon icon="heroicon-o-document-arrow-down" class="size-7" />
                    </span>
                    <span>
                        <strong>Archivo recibido</strong>
                        <small>{{ $receivedFile ? basename($receivedFile) : 'No adjuntado' }}</small>
                    </span>
                </a>
            </div>
        </section>

        <footer class="damian-order-audit">
            <span>Registro creado: {{ $order->created_at->format('d/m/Y H:i') }}</span>
            <span>Última actualización: {{ $order->updated_at->format('d/m/Y H:i') }}</span>
            <span>Vista de Gerencia · Solo lectura</span>
        </footer>
    </div>
</x-filament-panels::page>
