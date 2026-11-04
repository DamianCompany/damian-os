<x-filament-panels::page>
    @php
        $labels = ['orders'=>'Ventas y pedidos','tasks'=>'Mis trabajos / programación','production'=>'Producción diaria','purchases'=>'Compras y gastos','funds'=>'Fondos y rendiciones','customers'=>'Clientes','stock'=>'Materiales y productos','cash'=>'Caja y cobros','reports'=>'Reportes','settings'=>'Costos y configuración','audit'=>'Actividad'];
        $money = fn($c) => \App\Support\WorkshopNumber::money($c === null ? null : (int) $c);
        $state=fn($s)=>['void'=>'Anulada','cancelled'=>'Anulado','pending'=>'Pendiente','running'=>'En curso','paused'=>'Pausado','finished'=>'Terminado','in_progress'=>'En proceso','completed'=>'Completado','partial'=>'Parcial','paid'=>'Pagado','delivered'=>'Entregado','open'=>'Abierto','closed'=>'Cerrado','approved'=>'Aprobado','observed'=>'Observado','account'=>'Caja / cuenta','fund'=>'Fondo asignado','employee'=>'Dinero propio','credit'=>'Crédito','inventory'=>'Material / producto','direct'=>'Costo directo','overhead'=>'Gasto general','order'=>'Pedido','stock'=>'Stock','internal'=>'Interno','sample'=>'Muestra / prueba','machine'=>'Máquina','labor'=>'Mano de obra'][$s]??$s;
    @endphp
    <div class="workshop-shell space-y-5">
        <section class="damian-order-section">
            <h2 class="text-xl font-bold">{{ $admin ? 'Configura y supervisa tu taller' : '¿Qué vas a registrar?' }}</h2>
            <p class="mt-1 text-sm text-gray-500">Soles · Hora de Lima · Cada acción se registra una vez y conserva su historial.</p>
            <div class="mt-4 flex flex-wrap gap-3">
                <x-workshop-action :action="$this->saleAction" /> <x-workshop-action :action="$this->productionAction" /> <x-workshop-action :action="$this->purchaseAction" /> <x-workshop-action :action="$this->deliveryAction" />
            </div>
        </section>
        <nav class="flex flex-wrap gap-2" aria-label="Operación DAMI 3D">
            @foreach($tabs as $key)
                <button type="button" wire:click="$set('tab', '{{ $key }}')" @class(['rounded-xl border px-4 py-3 text-sm font-semibold','bg-primary-600 text-white'=>$tab===$key])>{{ $labels[$key] }}</button>
            @endforeach
        </nav>
        <section class="damian-order-section">
            <div class="flex flex-wrap items-end gap-3">
                <label class="min-w-48 flex-1 text-sm">Buscar<input aria-label="Buscar" class="workshop-input" wire:model.live.debounce.350ms="search" placeholder="Nombre, teléfono, documento o pedido"></label>
                <label class="text-sm">Desde<input class="workshop-input" type="date" wire:model.live="from"></label>
                <label class="text-sm">Hasta<input class="workshop-input" type="date" wire:model.live="until"></label>
                @if($admin)
                    <label class="text-sm">Trabajador<select class="workshop-input" wire:model.live="worker"><option value="">Todos</option>@foreach($workers as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></label>
                @endif
                @if(in_array($tab,['tasks','production','reports']))
                    <label class="text-sm">Máquina<select class="workshop-input" wire:model.live="machine"><option value="">Todas</option>@foreach($machines as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></label>
                @endif
                @if(in_array($tab,['orders','reports']))
                    <label class="text-sm">Cliente<select class="workshop-input" wire:model.live="customerFilter"><option value="">Todos</option>@foreach($customersFilter as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></label>
                @endif
                @if(in_array($tab,['stock','reports']))<label class="text-sm">Categoría<input class="workshop-input" wire:model.live.debounce.350ms="categoryFilter"></label>@endif
                @if(in_array($tab,['orders','tasks','production','purchases','reports']))<label class="text-sm">Pedido<select class="workshop-input" wire:model.live="orderFilter"><option value="">Todos</option>@foreach($ordersFilter as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></label>@endif
            </div>
            <div class="mt-4 flex flex-wrap gap-3">
                @switch($tab)
                    @case('orders') <x-workshop-action :action="$this->collectAction" /> <x-workshop-action :action="$this->orderEventAction" /> <x-workshop-action :action="$this->correctionAction" /> <x-workshop-action :action="$this->cancelOrderAction" /> @break
                    @case('tasks') <x-workshop-action :action="$this->taskAction" /> <x-workshop-action :action="$this->batchAction" /> <x-workshop-action :action="$this->correctTaskAction" /> @break
                    @case('customers') <x-workshop-action :action="$this->customerAction" /> @break
                    @case('funds') <x-workshop-action :action="$this->fundAction" /> <x-workshop-action :action="$this->returnAction" /> <x-workshop-action :action="$this->closeFundAction" /> @break
                    @case('purchases') <x-workshop-action :action="$this->payPurchaseAction" /> <x-workshop-action :action="$this->reviewAction" /> <x-workshop-action :action="$this->voidPurchaseAction" /> @break
                    @case('settings') <x-workshop-action :action="$this->configureAction" /> <x-workshop-action :action="$this->rateAction" /> <x-workshop-action :action="$this->recipeAction" /> <x-workshop-action :action="$this->recalculateAction" /> @break
                    @case('stock') <x-workshop-action :action="$this->adjustAction" /> @break
                    @case('cash') <x-workshop-action :action="$this->correctionAction" /> <x-workshop-action :action="$this->reverseAction" /> @break
                @endswitch
            </div>
        </section>

        @if($tab==='reports')
            <section class="damian-order-section">
                <h3 class="text-lg font-bold">Resultados estimados · detalle por periodo</h3>
                <p class="mt-2 text-sm text-gray-500">Ventas registradas y ventas entregadas son medidas distintas. El resultado estándar incluye solo el costo congelado de lo entregado; los gastos generales asignados no se vuelven a restar. Compras de stock, fondos y reembolsos no son nuevos costos de venta. Los costos incompletos impiden presentar una utilidad definitiva.</p>
                <button class="mt-4 rounded-xl border px-4 py-3 font-semibold" wire:click="export" type="button">Exportar CSV</button>
                <dl class="damian-order-data mt-4">@foreach($report as $label=>$value)
                    @php $detail=str_contains($label,'Fondo')?'funds':((str_contains($label,'Compra')||str_contains($label,'Reembolso')||str_contains($label,'reales'))?'purchases':((str_contains($label,'Tarea')||str_contains($label,'incidencia'))?'tasks':((str_contains($label,'produc')||str_contains($label,'manual')||str_contains($label,'Costo'))?'production':((str_contains($label,'Cajas')||str_contains($label,'cajas')||str_contains($label,'Cobro'))?'cash':'orders')))); @endphp
                    <div><dt><button type="button" class="text-left underline decoration-dotted" wire:click="$set('tab','{{ $detail }}')">{{ $label }} →</button></dt><dd>{{ is_numeric($value) ? number_format($value,is_int($value)?0:2) : $value }}</dd></div>
                @endforeach</dl>
                <p class="mt-4 text-sm text-gray-500">Importes internos tal como se registran, sin liquidación tributaria ni emisión electrónica. No se suman cantidades de productos diferentes como una medida de productividad.</p>
            </section>
        @endif
        @if($tab==='cash')
            <section class="damian-order-section"><h3 class="font-bold">Cajas y cuentas</h3><dl class="damian-order-data">@foreach($accounts as $account)<div><dt>{{ $account->name }}</dt><dd>{{ $money($account->balance_cents) }}</dd></div>@endforeach</dl>
                @foreach($corrections as $correction)<p class="mt-2 text-sm">Solicitud #{{ $correction->id }} · movimiento #{{ $correction->ledger_id }} · {{ $correction->reason }}</p>@endforeach
            </section>
        @endif
        @if($tab==='settings')<p class="text-sm text-gray-500">Tarifas nuevas con vigencia. Precedencia de mano de obra: trabajador, actividad, general. Cada producción conserva la tarifa aplicada. No se precargaron importes de ejemplo.</p>@endif
        @if($productionTotals->isNotEmpty())<section class="damian-order-section"><h3 class="font-bold">Terminadas por producto (según filtros)</h3><dl class="damian-order-data">@foreach($productionTotals as $total)<div><dt>{{ $total->description }}</dt><dd>{{ $total->finished_quantity }} unidades</dd></div>@endforeach</dl><p class="mt-2 text-sm text-gray-500">Las etapas intermedias no se suman como producto terminado.</p></section>@endif
        <div class="space-y-4">
        @forelse($rows as $row)
            <article class="damian-order-section" wire:key="{{ $tab }}-{{ $row->id }}">
                @switch($tab)
                @case('orders')
                    <div class="flex flex-wrap justify-between gap-3"><div><h3 class="text-lg font-bold">{{ $row->order_number }} · {{ $row->client_name }}</h3><p>{{ $row->description }}</p></div><strong>{{ $money($row->sale_cents) }}</strong></div>
                    <p class="mt-2 text-sm">{{ $row->document_kind === 'quote' ? 'Cotización' : ($row->document_kind === 'cancelled' ? 'Anulada' : 'Pedido') }} · Producción: {{ $state($row->status) }} · Pago: {{ $state($row->payment_status) }} · Entrega: {{ $state($row->delivery_status) }}</p>
                    <p class="mt-2 text-sm">Cobrado {{ $money($row->collected_cents) }} · Saldo {{ $money($row->sale_cents-$row->collected_cents) }} · Entrega {{ $row->delivery_date?->format('d/m/Y') }} @if($row->payment_due) · Vence {{ $row->payment_due->format('d/m/Y') }} @endif</p>
                    @if($row->design_required)<p class="mt-2 text-sm">Diseño: {{ $row->design_approved ? 'aprobado' : 'pendiente de aprobación' }}</p>@endif
                    @if($row->purchase_reference)<p class="text-sm">OC: {{ $row->purchase_reference }}</p>@endif
                    <ul class="mt-3 space-y-1 text-sm">@foreach($orderLines->get($row->id, collect()) as $line)<li>{{ $line->description }} · {{ $line->quantity }} unidades · terminadas {{ $line->produced }} · entregadas {{ $line->delivered }} · {{ $money($line->unit_cents) }} c/u</li>@endforeach</ul>
                    @if($admin)
                        @php
                            $entries=$costs->get($row->id,collect()); $direct=$directCosts->get($row->id,collect())->sum('amount_cents');
                            $complete=$entries->isNotEmpty() && $entries->every('cost_complete') && $orderLines->get($row->id,collect())->every(fn($l)=>$l->produced >= $l->quantity);
                            $cost=$entries->sum('cost_cents')+$direct; $overhead=$entries->sum(fn($p)=>$p->cost_snapshot['overhead']??0);
                            if($direct && $entries->contains(fn($p)=>($p->cost_snapshot['external']??0)>0)){$complete=false;}
                            $delivered=$deliveredCosts->get($row->id,collect());
                            if($row->delivery_status==='delivered' && $delivered->isNotEmpty()){
                                $complete=$delivered->every('cost_complete') && $delivered->every(fn($d)=>($d->cost_snapshot['direct_purchases_total']??0)==$direct);
                                $cost=$delivered->sum('cost_cents'); $overhead=$delivered->sum(fn($d)=>$d->cost_snapshot['overhead']??0);
                            }
                        @endphp
                        <p class="mt-3 text-sm">Costo total registrado: {{ $complete ? $money($cost) : 'Incompleto' }} · Resultado estimado después de generales asignados: {{ $complete ? $money($row->sale_cents-$cost) : 'No calculable todavía' }}</p>
                        @if($complete)<p class="text-sm">Margen antes de generales asignados {{ $money($row->sale_cents-$cost+$overhead) }} · Margen sobre ventas {{ $row->sale_cents ? number_format(($row->sale_cents-$cost)*100/$row->sale_cents,1).'%' : 'No aplica' }}</p>@endif
                        @foreach($orderLines->get($row->id,collect()) as $line)
                            @if($line->recipe_snapshot)<p class="text-sm">Estimado original de {{ $line->description }}: {{ $money($line->recipe_snapshot['estimate']['cost_cents']??null) }} (no se suma al consumo confirmado).</p>@endif
                        @endforeach
                    @endif
                    <div class="mt-3 flex flex-wrap gap-3"><x-workshop-action :action="$this->saleAction" :arguments="['duplicate'=>$row->id]" /> @foreach($row->attachments??[] as $index=>$path)<a class="underline" href="{{ route('workshop.file',['type'=>'order','id'=>$row->id,'index'=>$index]) }}" target="_blank" rel="noopener">Adjunto {{ $index+1 }}</a>@endforeach</div>
                    @if($row->document_kind==='quote')<x-workshop-action :action="$this->quoteAction" :arguments="['order'=>$row->id]" />@endif
                    @break
                @case('tasks')
                    <h3 class="text-lg font-bold">#{{ $row->id }} · {{ $row->description }}</h3><p class="mt-1 text-sm">{{ $row->activity }} · {{ $row->quantity }} unidades de esta etapa · {{ $state($row->status) }} · {{ $row->final_stage ? 'Etapa final' : 'Etapa intermedia, no suma producto terminado' }}</p>
                    <p class="text-sm">Responsable {{ $workers[$row->responsible_id]??$row->responsible_id }} · {{ $machines[$row->printer_id]??'Trabajo manual' }} · {{ $row->planned_start?->format('d/m H:i') }} → {{ $row->planned_end?->format('d/m H:i') }}</p>
                    <p class="text-sm">Acumulado: {{ round($row->manual_seconds/60,1) }} min manuales · {{ round($row->machine_seconds/60,1) }} min máquina</p>
                    @if($row->incident)<p class="mt-2 text-sm">Incidencia: {{ $row->incident }}</p>@endif
                    @if($row->file_path)<a class="underline" href="{{ route('workshop.file',['type'=>'task','id'=>$row->id]) }}">Archivo vigente</a>@endif
                    <div class="mt-4 flex flex-wrap gap-3">
                        @if($row->status==='pending')<x-workshop-action :action="$this->transitionAction" :arguments="['task'=>$row->id,'event'=>'start']" />@endif
                        @if($row->status==='running')<x-workshop-action :action="$this->transitionAction" :arguments="['task'=>$row->id,'event'=>'pause']" />@endif
                        @if($row->status==='paused')<x-workshop-action :action="$this->transitionAction" :arguments="['task'=>$row->id,'event'=>'resume']" />@endif
                        @if(in_array($row->status,['running','paused']))<x-workshop-action :action="$this->transitionAction" :arguments="['task'=>$row->id,'event'=>'finish']" />@endif
                        @if($row->status!=='finished')<x-workshop-action :action="$this->transitionAction" :arguments="['task'=>$row->id,'event'=>'problem']" />@endif
                    </div>
                    @break
                @case('production')
                    <h3 class="text-lg font-bold">{{ $row->description }}</h3><p>{{ $row->created_at->format('d/m/Y H:i') }} · {{ $workers[$row->user_id]??$row->user_id }} · {{ $row->activity }} · {{ $state($row->purpose) }}</p>
                    <p class="mt-2">{{ $row->quantity }} {{ $row->final_stage?'unidades terminadas':'unidades procesadas (etapa intermedia)' }} · Fallidas {{ $row->failed }}</p>
                    <p class="text-sm">Máquina {{ $row->machine_minutes }} min · Manual {{ $row->manual_minutes }} min</p>
                    @foreach($row->consumptions as $material)<p class="text-sm">Material #{{ $material['item_id'] }} · {{ $material['quantity'] }} · Desperdicio incluido {{ $material['waste']??0 }}</p>@endforeach
                    @if($admin)<p class="mt-2">Costo {{ $money($row->cost_cents) }} · {{ $row->cost_complete?'Confirmado con tarifas congeladas':'Faltan consumos o tarifas' }}</p>@endif
                    @if($admin)<x-workshop-action :action="$this->recipeAction" :arguments="['production'=>$row->id]" />@endif
                    @if($row->incident)<p>{{ $row->incident }}</p>@endif
                    @if($row->attachment)<a class="underline" href="{{ route('workshop.file',['type'=>'production','id'=>$row->id]) }}">Evidencia</a>@endif
                    @break
                @case('purchases')
                    <h3 class="text-lg font-bold">#{{ $row->id }} · {{ $row->description }} · {{ $money($row->amount_cents) }}</h3>
                    <p class="text-sm">{{ $row->purchased_on->format('d/m/Y') }} · {{ $state($row->payment_source) }} · {{ $state($row->category) }} · revisión: {{ $state($row->review) }}</p>
                    <p class="text-sm">Pagado {{ $money($row->paid_cents) }} @if($row->payment_source==='employee') · Por reembolsar {{ $money($row->amount_cents-$row->reimbursed_cents) }} @endif</p>
                    @if($row->review_note)<p>{{ $row->review_note }}</p>@endif
                    @if($row->receipt)<a class="underline" href="{{ route('workshop.file',['type'=>'purchase','id'=>$row->id]) }}">Comprobante</a>@endif
                    @break
                @case('funds')
                    <h3 class="text-lg font-bold">#{{ $row->id }} · {{ $row->purpose }}</h3><p>{{ $workers[$row->user_id]??$row->user_id }} · {{ $state($row->status) }}</p>
                    <p class="mt-2">Asignado {{ $money($row->assigned_cents) }} · Pendiente de justificar o devolver {{ $money($row->remaining_cents) }}</p>
                    @php $movements=$fundMovements->get($row->id,collect()); @endphp
                    <p class="text-sm">Gastos registrados {{ $money(-$movements->whereIn('kind',['fund_expense','fund_expense_reversal'])->sum('amount_cents')) }} · Devoluciones {{ $money($movements->where('kind','fund_return')->sum('amount_cents')) }}</p>
                    @break
                @case('customers')
                    <h3 class="text-lg font-bold">{{ $row->name }}</h3><p>{{ $row->type==='institution'?'Empresa / institución':'Persona natural' }} · {{ $row->phone }} · {{ $row->document }}</p><p>{{ $row->contact }} · {{ $row->address }} · {{ $row->payment_terms }}</p>
                    <div class="mt-3 flex flex-wrap gap-3"><x-workshop-action :action="$this->editCustomerAction" :arguments="['customer'=>$row->id]" /><button type="button" class="underline" wire:click="customerOrders({{ $row->id }})">Consultar pedidos y saldos</button></div>
                    @break
                @case('stock')
                    <h3 class="text-lg font-bold">{{ $row->favorite?'★ ':'' }}{{ $row->name }}</h3><p>{{ $row->kind==='material'?'Material':'Producto terminado' }} · {{ $row->category }} · Existencia {{ number_format($row->quantity_milli/1000,3) }} {{ $row->unit }}</p>
                    @if($admin)<p class="text-sm">Valor {{ $row->valuation_complete?$money($row->value_cents):'Incompleto' }} · 1 {{ $row->purchase_unit }} = {{ $row->conversion_milli/1000 }} {{ $row->unit }} · Receta versión {{ $row->version }}</p>@endif
                    @break
                @case('settings')
                    <h3 class="text-lg font-bold">{{ $row->name }} · {{ $money($row->hour_cents) }} / hora</h3><p>{{ $state($row->kind) }} · Vigente desde {{ $row->effective_on->format('d/m/Y') }} · Versión #{{ $row->id }}</p><p class="text-sm">{{ $row->activity }} {{ $workers[$row->user_id]??'' }} {{ $machines[$row->printer_id]??'' }}</p>
                    <x-workshop-action :action="$this->rateAction" :arguments="['rate'=>$row->id]" />
                    @break
                @case('cash')
                    <h3 class="font-bold">#{{ $row->id }} · {{ $row->kind }} · {{ $money($row->amount_cents) }}</h3><p>{{ $row->created_at->format('d/m/Y H:i') }} · Usuario {{ $workers[$row->user_id]??$row->user_id }} · Cuenta #{{ $row->account_id }} · Pedido #{{ $row->dami_order_id }} · Compra #{{ $row->purchase_id }} · Fondo #{{ $row->fund_id }}</p><p>{{ $row->reason }}</p>
                    @break
                @case('audit')
                    <h3 class="font-bold">{{ $row->action }} · {{ $row->subject }}</h3><p>{{ $row->created_at->format('d/m/Y H:i:s') }} · {{ $workers[$row->user_id]??$row->user_id }}</p>
                    <details class="mt-2"><summary>Ver cambios registrados</summary><pre class="mt-2 overflow-auto text-xs">{{ json_encode($row->changes, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre></details>
                    @break
                @endswitch
            </article>
        @empty
            @if($tab!=='reports')<div class="damian-order-section"><strong>Aún no hay registros para estos filtros.</strong><p class="mt-2 text-sm text-gray-500">Usa las acciones de arriba para comenzar. Se muestran hasta 100 registros recientes.</p></div>@endif
        @endforelse
        </div>
    </div>
</x-filament-panels::page>
