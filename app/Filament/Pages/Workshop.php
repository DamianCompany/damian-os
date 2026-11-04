<?php

namespace App\Filament\Pages;

use App\Models\DamiOrder;
use App\Models\Printer;
use App\Models\ProveedorDami3d;
use App\Models\User;
use App\Models\Workshop\Account;
use App\Models\Workshop\Activity;
use App\Models\Workshop\Audit;
use App\Models\Workshop\Batch;
use App\Models\Workshop\Correction;
use App\Models\Workshop\Customer;
use App\Models\Workshop\Delivery;
use App\Models\Workshop\Fund;
use App\Models\Workshop\Item;
use App\Models\Workshop\Ledger;
use App\Models\Workshop\OrderLine;
use App\Models\Workshop\PaymentMethod;
use App\Models\Workshop\Production;
use App\Models\Workshop\Purchase;
use App\Models\Workshop\Rate;
use App\Models\Workshop\Task;
use App\Services\WorkshopService;
use App\Support\WorkshopNumber as N;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;

class Workshop extends Page
{
    protected string $view = 'filament.pages.workshop';

    protected static ?string $title = 'Operación DAMI 3D';

    protected static ?string $navigationLabel = 'Operación diaria';

    protected static string|\UnitEnum|null $navigationGroup = 'DAMI 3D';

    protected static ?int $navigationSort = 0;

    #[Url]
    public string $tab = 'orders';

    #[Url]
    public string $search = '';

    #[Url]
    public string $from = '';

    #[Url]
    public string $until = '';

    #[Url]
    public string $worker = '';

    #[Url]
    public string $machine = '';

    #[Url]
    public string $customerFilter = '';

    #[Url]
    public string $categoryFilter = '';

    #[Url]
    public string $orderFilter = '';

    #[Locked]
    public string $operationToken;

    public static function canAccess(): bool
    {
        return auth()->user()?->canWorkshop('sale') || auth()->user()?->canWorkshop('production') || auth()->user()?->canWorkshop('purchase');
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->operationToken = (string) Str::uuid();
    }

    public function admin(): bool
    {
        return auth()->user()?->isWorkshopAdmin() ?? false;
    }

    public function permitted(string $action): bool
    {
        return auth()->user()?->canWorkshop($action) ?? false;
    }

    private function run(string $action, array $data): void
    {
        $result = app(WorkshopService::class)->execute(auth()->user(), $action, $data, $this->operationToken);
        $this->operationToken = (string) Str::uuid();
        $warning = ! empty($result['duplicate_warning']) || ! empty($result['overlap_warning']);
        Notification::make()->title($warning ? 'Guardado; revisa la posible coincidencia o solapamiento' : 'Registro guardado')
            ->body('Referencia: '.$result['subject'])->status($warning ? 'warning' : 'success')->send();
    }

    private function text(string $name, string $label, bool $required = true): TextInput
    {
        return TextInput::make($name)->label($label)->maxLength(255)->required($required);
    }

    private function number(string $name, string $label, mixed $default = 0, bool $integer = false): TextInput
    {
        $field = TextInput::make($name)->label($label)->numeric()->minValue(0)->maxValue(10000000)->default($default)->required();

        return $integer ? $field->integer() : $field;
    }

    private function notes(string $name = 'description', string $label = 'Descripción', bool $required = true): Textarea
    {
        return Textarea::make($name)->label($label)->maxLength(10000)->rows(2)->required($required)->extraAttributes(['data-dictation' => 'es-PE']);
    }

    private function choice(string $name, string $label, array|\Closure $options, bool $required = true): Select
    {
        return Select::make($name)->label($label)->options($options)->searchable()->required($required)->native(false);
    }

    private function upload(string $name = 'receipt'): FileUpload
    {
        return FileUpload::make($name)->label('Foto o comprobante (opcional)')->disk('local')->directory('workshop/attachments')->visibility('private')->maxSize(10240)->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'application/zip', 'model/stl']);
    }

    private function orders(): array
    {
        return DamiOrder::where('workshop', true)->where('document_kind', 'order')->latest()->limit(200)->get()->mapWithKeys(fn ($o) => [$o->id => $o->order_number.' · '.$o->client_name])->all();
    }

    private function lines(): array
    {
        return OrderLine::join('dami_orders', 'dami_orders.id', '=', 'workshop_order_lines.dami_order_id')->where('dami_orders.document_kind', 'order')
            ->orderByDesc('workshop_order_lines.id')->limit(200)->get(['workshop_order_lines.*', 'dami_orders.order_number'])->mapWithKeys(fn ($l) => [$l->id => $l->order_number.' · '.$l->description.' ('.$l->quantity.')'])->all();
    }

    private function batches(): array
    {
        return Batch::whereColumn('completed', '<', 'quantity')->latest()->limit(200)->get()->mapWithKeys(fn ($b) => [$b->id => '#'.$b->id.' '.$b->name.' · '.$b->completed.'/'.$b->quantity])->all();
    }

    public function customerOrders(int $id): void
    {
        $this->customerFilter = (string) $id;
        $this->tab = 'orders';
    }

    private function items(string $kind = 'material'): array
    {
        return Item::where('kind', $kind)->where('active', true)->orderByDesc('favorite')->orderBy('name')->limit(500)->get()->mapWithKeys(fn ($i) => [$i->id => $i->name.' · '.$i->unit])->all();
    }

    private function workers(): array
    {
        return User::whereIn('role', ['dami_3d', 'gerencia'])->where('is_active', true)->pluck('name', 'id')->all();
    }

    private function payment(): array
    {
        return [$this->choice('account_id', 'Caja o cuenta', fn () => Account::where('active', true)->pluck('name', 'id')->all()), $this->choice('method_id', 'Medio de pago', fn () => PaymentMethod::pluck('name', 'id')->all())];
    }

    private function consumptionFields(string $name = 'consumptions'): Repeater
    {
        return Repeater::make($name)->label('Materiales · cantidades en unidad de consumo')->schema([
            $this->choice('item_id', 'Material', fn () => $this->items()), $this->number('quantity', 'Cantidad total consumida (incluye desperdicio)', 1),
            $this->number('waste', 'Desperdicio incluido (opcional)', 0),
        ])->columns(2)->defaultItems(0)->addActionLabel('Añadir material')->maxItems(30);
    }

    public function customerAction(): Action
    {
        return Action::make('customer')->label('Nuevo cliente')->visible(fn () => $this->permitted('customer'))->schema([
            $this->text('name', 'Nombre'), $this->choice('type', 'Tipo', ['person' => 'Persona natural', 'institution' => 'Empresa / institución'])->default('person'),
            Section::make('Más opciones')->collapsed()->schema([$this->text('phone', 'Teléfono', false), $this->text('document', 'Documento / RUC', false),
                $this->text('contact', 'Contacto', false), $this->text('address', 'Dirección', false), $this->text('payment_terms', 'Condiciones de pago', false)]),
        ])->action(fn (array $data) => $this->run('customer', $data));
    }

    public function editCustomerAction(): Action
    {
        return Action::make('editCustomer')->label('Completar cliente')->visible(fn () => $this->permitted('customer'))
            ->fillForm(fn (array $arguments) => Customer::findOrFail($arguments['customer'])->only(['name', 'type', 'phone', 'document', 'address', 'contact', 'payment_terms', 'version']) + ['customer_id' => $arguments['customer']])
            ->schema([TextInput::make('customer_id')->hidden(), TextInput::make('version')->hidden(), $this->text('name', 'Nombre'),
                $this->choice('type', 'Tipo', ['person' => 'Persona natural', 'institution' => 'Empresa / institución']), $this->text('phone', 'Teléfono', false),
                $this->text('document', 'Documento / RUC', false), $this->text('address', 'Dirección', false), $this->text('contact', 'Contacto', false), $this->text('payment_terms', 'Condiciones de pago', false)])
            ->action(fn (array $data) => $this->run('updateCustomer', $data));
    }

    public function saleAction(): Action
    {
        return Action::make('sale')->label('Nueva venta / pedido')->icon('heroicon-o-plus')->visible(fn () => $this->permitted('sale'))
            ->fillForm(function (array $arguments): array {
                $order = empty($arguments['duplicate']) ? null : DamiOrder::where('workshop', true)->findOrFail($arguments['duplicate']);
                if (! $order) {
                    return ['kind' => 'custom', 'received' => 0, 'delivery_date' => today()->toDateString(), 'lines' => [['quantity' => 1, 'price' => 0]]];
                }

                return ['kind' => 'custom', 'customer_id' => $order->customer_id, 'received' => 0, 'delivery_date' => today()->toDateString(),
                    'lines' => OrderLine::where('dami_order_id', $order->id)->get()->map(fn ($l) => ['item_id' => $l->item_id, 'description' => $l->description, 'quantity' => $l->quantity, 'price' => $l->unit_cents / 100])->all()];
            })->schema([
                $this->choice('kind', 'Registrar como', ['custom' => 'Pedido personalizado', 'stock' => 'Venta de producto terminado', 'quote' => 'Cotización'])->default('custom')->live(),
                $this->choice('customer_id', 'Cliente (vacío = ocasional)', fn () => Customer::orderByDesc('id')->limit(100)->pluck('name', 'id')->all(), false)
                    ->getSearchResultsUsing(fn (string $search): array => Customer::where(fn ($q) => $q->where('name', 'like', "%$search%")
                        ->orWhere('phone', 'like', "%$search%")->orWhere('document', 'like', "%$search%"))->limit(40)->pluck('name', 'id')->all())
                    ->getOptionLabelUsing(fn ($value) => Customer::find($value)?->name)
                    ->createOptionForm([$this->text('name', 'Nombre'), $this->text('phone', 'Teléfono', false)])
                    ->createOptionUsing(fn (array $data) => app(WorkshopService::class)->execute(auth()->user(), 'customer', $data + ['type' => 'person'], (string) Str::uuid())['id']),
                Repeater::make('lines')->label('Productos')->schema([
                    $this->choice('item_id', 'Producto de catálogo (opcional)', fn () => $this->items('product'), false)->live()
                        ->afterStateUpdated(function ($state, Set $set): void {
                            if ($item = Item::find($state)) {
                                $set('description', $item->name);
                                $set('price', ($item->sale_price_cents ?? 0) / 100);
                            }
                        }),
                    $this->notes(), $this->number('quantity', 'Cantidad', 1, true)->minValue(1)->live(), $this->number('price', 'Precio unitario S/', 0)->live(),
                ])->minItems(1)->maxItems(30)->defaultItems(1)->addActionLabel('Otro producto')->columns(2)->live(),
                Text::make(fn (Get $get) => 'Total: '.N::money(array_sum(array_map(fn ($l) => is_numeric($l['price'] ?? null) && is_numeric($l['quantity'] ?? null) ? (int) round($l['price'] * 100) * (int) $l['quantity'] : 0, $get('lines') ?? [])))),
                $this->number('received', 'Pago recibido S/', 0)->visible(fn (Get $get) => $get('kind') !== 'quote'),
                Section::make('Destino del cobro')->schema($this->payment())->columns(2)->visible(fn (Get $get) => (float) $get('received') > 0 && $get('kind') !== 'quote'),
                DatePicker::make('delivery_date')->label('Fecha comprometida')->default(today())->required(),
                Toggle::make('immediate')->label('Guardar y entregar desde stock')->visible(fn (Get $get) => $get('kind') === 'stock')->default(false),
                Section::make('Más opciones')->collapsed()->schema([
                Toggle::make('design_required')->label('Necesita aprobación de diseño')->default(false), DatePicker::make('payment_due')->label('Vencimiento de pago'),
                $this->text('purchase_reference', 'Orden de compra / referencia', false), $this->text('delivery_address', 'Dirección de entrega', false),
                $this->notes('notes', 'Medidas, material, color, acabado y observaciones', false), $this->upload('attachments')->multiple()->maxFiles(5),
            ]),
            ])->modalSubmitActionLabel('Guardar')->action(function (array $data): void {
                if ($data['kind'] === 'quote') {
                    $data['received'] = 0;
                    $data['immediate'] = false;
                }
                $this->run('sale', $data);
            });
    }

    public function collectAction(): Action
    {
        return Action::make('collect')->label('Registrar cobro')->visible(fn () => $this->permitted('collect'))->schema([
            $this->choice('order_id', 'Pedido', fn () => $this->orders()), $this->number('amount', 'Importe S/'), ...$this->payment(),
        ])->action(fn (array $data) => $this->run('collect', $data));
    }

    public function quoteAction(): Action
    {
        return Action::make('quote')->label('Revisar cotización')->visible(fn () => $this->permitted('sale'))
            ->fillForm(function (array $arguments): array {
                $o = DamiOrder::where('workshop', true)->where('document_kind', 'quote')->findOrFail($arguments['order']);

                return ['order_id' => $o->id, 'version' => $o->version, 'lines' => OrderLine::where('dami_order_id', $o->id)->get()->map(fn ($l) => ['item_id' => $l->item_id, 'description' => $l->description, 'quantity' => $l->quantity, 'price' => $l->unit_cents / 100])->all()];
            })->schema([TextInput::make('order_id')->hidden(), TextInput::make('version')->hidden(),
                Repeater::make('lines')->label('Productos cotizados')->schema([$this->choice('item_id', 'Producto', fn () => $this->items('product'), false), $this->notes(),
                    $this->number('quantity', 'Cantidad', 1, true)->minValue(1), $this->number('price', 'Precio unitario S/', 0)])->minItems(1)->maxItems(30)])
            ->action(fn (array $data) => $this->run('reviseQuote', $data));
    }

    public function orderEventAction(): Action
    {
        return Action::make('orderEvent')->label('Acción de pedido')->visible(fn () => $this->permitted('sale'))->schema([
            $this->choice('order_id', 'Pedido / cotización', fn () => DamiOrder::where('workshop', true)->latest()->limit(200)->pluck('order_number', 'id')->all()),
            $this->choice('event', 'Acción', ['convert' => 'Convertir cotización en pedido', 'approveDesign' => 'Confirmar aprobación del diseño', 'cancelQuote' => 'Anular cotización']),
        ])->action(function (array $data): void {
            $event = $data['event'];
            unset($data['event']);
            $this->run($event, $data);
        });
    }

    public function deliveryAction(): Action
    {
        return Action::make('delivery')->label('Entregar pedido')->visible(fn () => $this->permitted('deliver'))->schema([
            $this->choice('line_id', 'Producto del pedido', fn () => $this->lines()), $this->number('quantity', 'Cantidad entregada', 1, true)->minValue(1),
            $this->choice('source', 'Origen', ['production' => 'Producción del pedido', 'stock' => 'Producto terminado en stock'])->default('production'),
            $this->choice('mode', 'Modalidad', ['pickup' => 'Recojo', 'shipping' => 'Envío', 'immediate' => 'Entrega inmediata'])->default('pickup'),
            $this->text('reference', 'Referencia / evidencia', false),
        ])->action(fn (array $data) => $this->run('deliver', $data));
    }

    private function productionFields(bool $task = false): array
    {
        return [
            ...($task ? [] : [$this->notes(), $this->choice('purpose', 'Destino', ['order' => 'Pedido', 'stock' => 'Stock', 'internal' => 'Trabajo interno', 'sample' => 'Prueba / muestra'])->default('order'),
                $this->choice('line_id', 'Línea del pedido', fn () => $this->lines(), false),
                $this->choice('batch_id', 'Lote para stock (si tiene varias etapas)', fn () => $this->batches(), false),
                $this->choice('item_id', 'Producto para stock (opcional)', fn () => $this->items('product'), false)->live()
                    ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                        $this->fillRecipe($state, (int) ($get('quantity') ?: 1), $set);
                    }),
                $this->number('quantity', 'Cantidad terminada en esta etapa', 1, true)->minValue(1)->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, Get $get, Set $set) => $this->fillRecipe($get('item_id'), (int) $state, $set)),
                $this->choice('activity', 'Actividad', self::activities())->default('Impresión 3D'),
                $this->choice('printer_id', 'Máquina (opcional)', fn () => Printer::where('is_active', true)->pluck('name', 'id')->all(), false),
                $this->number('machine_minutes', 'Minutos de máquina', 0, true), $this->number('manual_minutes', 'Minutos manuales', 0, true),
                Toggle::make('final_stage')->label('Estas unidades son producto terminado')->default(true),
            ]),
            $this->number('failed', 'Unidades fallidas', 0, true), $this->consumptionFields(),
            Toggle::make('materials_confirmed')->label('Confirmé los consumos (también si no se usaron materiales)')->default(false),
            $this->number('external', 'Servicios externos S/ (no repetir compras ya registradas)', 0),
            Section::make('Incidencias y evidencia')->collapsed()->schema([$this->notes('incident', 'Incidencia / desperdicio', false), $this->upload('attachment')]),
        ];
    }

    public static function activities(): array
    {
        return collect(['Impresión 3D', 'Corte y grabado láser', 'Diseño', 'Armado', 'Pintura y acabados', 'Revisión y embalaje', 'Otra actividad'])
            ->merge(Activity::pluck('name'))->unique()->mapWithKeys(fn ($v) => [$v => $v])->all();
    }

    private function fillRecipe(mixed $id, int $quantity, Set $set): void
    {
        $item = Item::find($id);
        if (! $item?->recipe) {
            return;
        }
        $r = $item->recipe;
        $batch = max(1, (int) $item->recipe_batch);
        $set('description', $item->name);
        $set('printer_id', $r['printer_id'] ?? null);
        $set('activity', $r['activity']);
        $set('machine_minutes', (int) ceil($quantity / $batch) * (int) $r['setup_minutes'] + (int) ceil($r['machine_minutes'] * $quantity / $batch));
        $set('manual_minutes', (int) ceil($r['manual_minutes'] * $quantity / $batch));
        $set('external', round((float) $r['external'] * $quantity / $batch, 2));
        $set('consumptions', array_map(fn ($l) => ['item_id' => $l['item_id'], 'quantity' => round((float) $l['quantity'] * $quantity / $batch, 3)], $r['materials']));
    }

    public function productionAction(): Action
    {
        return Action::make('production')->label('Registrar producción')->visible(fn () => $this->permitted('production'))->schema($this->productionFields())
            ->action(fn (array $data) => $this->run('production', $data));
    }

    public function taskAction(): Action
    {
        return Action::make('task')->label($this->admin() ? 'Asignar trabajo' : 'Nuevo trabajo')->visible(fn () => $this->permitted('task'))->schema([
            $this->notes(), $this->choice('purpose', 'Destino', ['order' => 'Pedido', 'stock' => 'Stock', 'internal' => 'Interno', 'sample' => 'Prueba'])->default('order'),
            $this->choice('line_id', 'Línea del pedido', fn () => $this->lines(), false), $this->choice('item_id', 'Producto para stock', fn () => $this->items('product'), false),
            $this->choice('batch_id', 'Lote para stock', fn () => $this->batches(), false),
            $this->choice('activity', 'Actividad', self::activities()), $this->number('quantity', 'Cantidad de esta tarea', 1, true)->minValue(1),
            $this->choice('printer_id', 'Máquina', fn () => Printer::where('is_active', true)->pluck('name', 'id')->all(), false),
            $this->choice('responsible_id', 'Responsable', fn () => $this->admin() ? $this->workers() : [auth()->id() => auth()->user()->name])->default(fn () => auth()->id()),
            DateTimePicker::make('planned_start')->label('Inicio previsto'), DateTimePicker::make('planned_end')->label('Fin previsto'),
            Toggle::make('final_stage')->label('La tarea termina el producto')->default(false), $this->upload('file_path'),
        ])->action(fn (array $data) => $this->run('task', $data));
    }

    public function transitionAction(): Action
    {
        return Action::make('transition')->label(fn (array $arguments) => match ($arguments['event'] ?? '') {
            'start' => 'Iniciar', 'pause' => 'Pausar', 'resume' => 'Reanudar', 'finish' => 'Terminar', 'problem' => 'Reportar problema', default => 'Actualizar tarea',
        })->visible(fn () => $this->permitted('production'))
            ->fillForm(function (array $arguments): array {
                $t = Task::findOrFail($arguments['task']);
                abort_unless($this->admin() || $t->responsible_id === auth()->id(), 403);

                return ['task_id' => $t->id, 'version' => $t->version, 'event' => $arguments['event'], 'track_manual' => false, 'failed' => 0, 'external' => 0, 'materials_confirmed' => false, 'consumptions' => []];
            })->schema([
                TextInput::make('task_id')->hidden()->required(), TextInput::make('version')->hidden()->required(), TextInput::make('event')->hidden()->required(),
                Toggle::make('track_manual')->label('Cronometrar mi trabajo manual (solo una tarea a la vez)')->visible(fn (Get $get) => in_array($get('event'), ['start', 'resume'])),
                $this->notes('incident', 'Problema', true)->visible(fn (Get $get) => $get('event') === 'problem'),
                Section::make('Confirmar producción al terminar')->schema($this->productionFields(true))->visible(fn (Get $get) => $get('event') === 'finish'),
            ])->action(fn (array $data) => $this->run('transition', $data));
    }

    public function batchAction(): Action
    {
        return Action::make('batch')->label('Nuevo lote para stock')->visible(fn () => $this->permitted('task'))->schema([
            $this->text('name', 'Nombre del lote'), $this->choice('item_id', 'Producto', fn () => $this->items('product')), $this->number('quantity', 'Cantidad total prevista', 1, true)->minValue(1),
        ])->action(fn (array $data) => $this->run('batch', $data));
    }

    public function purchaseAction(): Action
    {
        return Action::make('purchase')->label('Registrar compra / gasto')->visible(fn () => $this->permitted('purchase'))->schema([
            $this->notes(), $this->choice('category', 'Uso', ['inventory' => 'Compra de materiales / productos', 'direct' => 'Costo directo de un pedido', 'overhead' => 'Gasto general'])->default('overhead')->live(),
            $this->number('amount', 'Importe total S/'),
            $this->choice('source', 'Se pagó con', ['account' => 'Caja / cuenta de la empresa', 'fund' => 'Mi fondo asignado', 'employee' => 'Dinero propio (por reembolsar)', 'credit' => 'A crédito'])->default('account')->live(),
            Section::make('Pago desde caja')->schema($this->payment())->visible(fn (Get $get) => $get('source') === 'account'),
            $this->choice('fund_id', 'Mi fondo', fn () => Fund::where('user_id', auth()->id())->where('status', 'open')->get()->mapWithKeys(fn ($f) => [$f->id => $f->purpose.' · '.N::money($f->remaining_cents)])->all())->visible(fn (Get $get) => $get('source') === 'fund'),
            Repeater::make('lines')->label('Detalle · cantidades en unidad de compra')->schema([
                $this->choice('item_id', 'Material / producto', fn () => Item::where('active', true)->get()->mapWithKeys(fn ($i) => [$i->id => $i->name.' · '.$i->purchase_unit])->all()),
                $this->number('quantity', 'Cantidad comprada', 1), $this->number('amount', 'Importe de esta línea S/'),
            ])->columns(3)->defaultItems(0)->maxItems(30)->visible(fn (Get $get) => $get('category') === 'inventory'),
            $this->choice('supplier_id', 'Proveedor (opcional)', fn () => ProveedorDami3d::pluck('razon_social', 'id')->all(), false)
                ->createOptionForm([$this->text('name', 'Nombre'), $this->text('phone', 'Teléfono', false)])
                ->createOptionUsing(fn (array $data) => app(WorkshopService::class)->execute(auth()->user(), 'supplier', $data, (string) Str::uuid())['id']),
            $this->choice('order_id', 'Pedido relacionado', fn () => $this->orders(), false),
            DatePicker::make('purchased_on')->label('Fecha de compra')->default(today())->maxDate(today())->required(), $this->upload(),
        ])->action(function (array $data): void {
            $data['lines'] = $data['category'] === 'inventory' ? ($data['lines'] ?? []) : [];
            $this->run('purchase', $data);
        });
    }

    public function fundAction(): Action
    {
        return Action::make('fund')->label('Asignar fondos')->visible(fn () => $this->admin())->schema([
            $this->choice('user_id', 'Trabajador', fn () => $this->workers()), $this->text('purpose', 'Finalidad'), $this->number('amount', 'Importe S/'),
            ...$this->payment(), $this->choice('order_id', 'Pedido (opcional)', fn () => $this->orders(), false),
        ])->action(fn (array $data) => $this->run('assignFund', $data));
    }

    public function returnAction(): Action
    {
        return Action::make('return')->label('Devolver saldo del fondo')->visible(fn () => $this->permitted('purchase'))->schema([
            $this->choice('fund_id', 'Fondo', fn () => Fund::where('status', 'open')->when(! $this->admin(), fn ($q) => $q->where('user_id', auth()->id()))->pluck('purpose', 'id')->all()),
            $this->number('amount', 'Importe devuelto S/'), ...$this->payment(),
        ])->action(fn (array $data) => $this->run('returnFund', $data));
    }

    public function payPurchaseAction(): Action
    {
        return Action::make('payPurchase')->label('Pagar compra / reembolsar')->visible(fn () => $this->admin())->schema([
            $this->choice('purchase_id', 'Compra', fn () => Purchase::whereIn('payment_source', ['employee', 'credit'])->latest()->limit(200)->get()
                ->mapWithKeys(fn ($p) => [$p->id => '#'.$p->id.' '.$p->description.' · '.N::money($p->amount_cents - ($p->payment_source === 'employee' ? $p->reimbursed_cents : $p->paid_cents))])->all()),
            $this->number('amount', 'Importe S/'), ...$this->payment(),
        ])->action(fn (array $data) => $this->run('payPurchase', $data));
    }

    public function reviewAction(): Action
    {
        return Action::make('review')->label('Revisar compra')->visible(fn () => $this->admin())->schema([
            $this->choice('purchase_id', 'Compra / gasto', fn () => Purchase::latest()->limit(200)->get()->mapWithKeys(fn ($p) => [$p->id => '#'.$p->id.' '.$p->description])->all()),
            $this->choice('review', 'Revisión', ['approved' => 'Aprobado', 'observed' => 'Observado']), $this->notes('note', 'Observación', false),
        ])->action(fn (array $data) => $this->run('reviewPurchase', $data));
    }

    public function closeFundAction(): Action
    {
        return Action::make('closeFund')->label('Cerrar rendición')->visible(fn () => $this->admin())->schema([
            $this->choice('fund_id', 'Fondo', fn () => Fund::where('status', 'open')->pluck('purpose', 'id')->all()),
        ])->action(fn (array $data) => $this->run('closeFund', $data));
    }

    public function correctionAction(): Action
    {
        return Action::make('correction')->label('Solicitar corrección')->visible(fn () => $this->permitted('collect'))->schema([
            $this->choice('ledger_id', 'Movimiento', fn () => Ledger::when(! $this->admin(), fn ($q) => $q->where('user_id', auth()->id()))->latest('id')->limit(200)->get()
                ->mapWithKeys(fn ($l) => [$l->id => '#'.$l->id.' '.$l->kind.' · '.N::money($l->amount_cents)])->all()), $this->notes('reason', 'Motivo'),
        ])->action(fn (array $data) => $this->run('requestCorrection', $data));
    }

    public function reverseAction(): Action
    {
        return Action::make('reverse')->label('Autorizar reverso financiero')->color('warning')->visible(fn () => $this->admin())->schema([
            $this->choice('ledger_id', 'Movimiento original', fn () => Ledger::whereIn('kind', ['collection', 'purchase_payment', 'reimbursement', 'fund_transfer', 'fund_return'])->latest('id')->limit(200)->get()->mapWithKeys(fn ($l) => [$l->id => '#'.$l->id.' '.$l->kind.' · '.N::money($l->amount_cents)])->all()),
            $this->notes('reason', 'Motivo de autorización'),
        ])->action(fn (array $data) => $this->run('reverse', $data));
    }

    public function voidPurchaseAction(): Action
    {
        return Action::make('voidPurchase')->label('Anular compra no consumida')->color('warning')->visible(fn () => $this->admin())->schema([
            $this->choice('purchase_id', 'Compra', fn () => Purchase::where('review', '!=', 'void')->latest()->limit(200)->get()->mapWithKeys(fn ($p) => [$p->id => '#'.$p->id.' '.$p->description])->all()),
            $this->notes('reason', 'Motivo'), Toggle::make('refund_confirmed')->label('Confirmé la devolución real del dinero pagado o la cancelación de la deuda')->required()->accepted(),
        ])->action(fn (array $data) => $this->run('voidPurchase', $data));
    }

    public function cancelOrderAction(): Action
    {
        return Action::make('cancelOrder')->label('Autorizar anulación de pedido')->color('warning')->visible(fn () => $this->admin())->schema([
            $this->choice('order_id', 'Pedido sin producción ni entrega', fn () => $this->orders()), $this->notes('reason', 'Motivo'),
        ])->action(fn (array $data) => $this->run('cancelOrder', $data));
    }

    public function configureAction(): Action
    {
        return Action::make('configure')->label('Configurar')->visible(fn () => $this->admin())->schema([
            $this->choice('type', 'Configurar', ['item' => 'Material o producto', 'machine' => 'Máquina', 'account' => 'Caja / cuenta', 'method' => 'Medio de pago', 'supplier' => 'Proveedor', 'activity' => 'Actividad de producción'])->live(),
            $this->text('name', 'Nombre'),
            $this->choice('kind', 'Tipo', ['material' => 'Material', 'product' => 'Producto terminado'])->default('material')->visible(fn (Get $get) => $get('type') === 'item'),
            Section::make('Unidades y catálogo')->description('Conversión explícita: 1 unidad de compra equivale a esta cantidad de unidades de consumo. No convertir volumen a peso sin factor conocido.')->schema([
                $this->text('purchase_unit', 'Unidad de compra')->default('unidad'), $this->text('unit', 'Unidad de consumo')->default('unidad'),
                $this->number('conversion', 'Factor de conversión', 1)->minValue(0.001), $this->number('price', 'Precio de venta sugerido S/', 0),
                $this->text('category', 'Categoría', false), Toggle::make('favorite')->label('Favorito'),
            ])->visible(fn (Get $get) => $get('type') === 'item'),
            $this->text('location', 'Ubicación')->visible(fn (Get $get) => $get('type') === 'machine'),
            $this->number('opening', 'Saldo inicial real S/', 0)->visible(fn (Get $get) => $get('type') === 'account'),
            $this->text('phone', 'Teléfono', false)->visible(fn (Get $get) => $get('type') === 'supplier'),
        ])->action(fn (array $data) => $this->run('configure', $data));
    }

    public function rateAction(): Action
    {
        return Action::make('rate')->label(fn (array $arguments) => empty($arguments['rate']) ? 'Nueva tarifa / vigencia' : 'Nueva vigencia de esta tarifa')->visible(fn () => $this->admin())
            ->fillForm(fn (array $arguments) => empty($arguments['rate']) ? ['kind' => 'labor', 'mode' => 'manual', 'effective_on' => today()->toDateString()]
                : array_replace(Rate::findOrFail($arguments['rate'])->parameters, ['effective_on' => today()->toDateString()]))->schema([
                    $this->choice('kind', 'Costo', ['machine' => 'Máquina', 'labor' => 'Mano de obra', 'overhead' => 'Gastos generales'])->default('labor')->live(),
                    $this->text('name', 'Nombre / concepto'), DatePicker::make('effective_on')->label('Vigente desde')->default(today())->required(),
                    $this->choice('mode', 'Modo excluyente', ['manual' => 'Tarifa total por hora', 'breakdown' => 'Cálculo desglosado / mensual'])->default('manual')->live(),
                    $this->choice('printer_id', 'Máquina', fn () => Printer::pluck('name', 'id')->all(), false)->visible(fn (Get $get) => $get('kind') === 'machine'),
                    $this->choice('user_id', 'Trabajador (opcional)', fn () => $this->workers(), false)->visible(fn (Get $get) => $get('kind') === 'labor'),
                    $this->choice('activity', 'Actividad (opcional; vacío = general)', self::activities(), false)->visible(fn (Get $get) => $get('kind') === 'labor'),
                    $this->number('hour_rate', 'Tarifa total S/ por hora')->visible(fn (Get $get) => $get('mode') === 'manual' && $get('kind') !== 'overhead'),
                    Section::make('Máquina: electricidad + depreciación + mantenimiento + consumibles')->description('Usar consumo promedio del equipo completo y auxiliares. No la potencia del tubo láser.')->schema([
                        $this->number('power_kw', 'Consumo promedio kW'), $this->number('electricity_rate', 'Tarifa eléctrica S/ por kWh'),
                        $this->number('depreciable', 'Costo depreciable S/'), $this->number('life_hours', 'Vida útil en horas', 1)->minValue(0.001),
                        $this->number('maintenance', 'Mantenimiento S/ por hora'), $this->number('consumables', 'Consumibles S/ por hora'),
                    ])->visible(fn (Get $get) => $get('kind') === 'machine' && $get('mode') === 'breakdown'),
                    Section::make('Costo mensual / capacidad normal')->description('Gastos generales: solo conceptos no incluidos en máquinas o costos directos. Se distribuyen por horas manuales. Una nueva tarifa no cambia registros anteriores.')->schema([
                        $this->number('monthly', 'Costo o presupuesto mensual S/'), $this->number('capacity', 'Horas productivas normales por mes', 1)->minValue(0.001),
                        $this->number('business_percent', 'Porcentaje atribuible al negocio', 100)->maxValue(100),
                    ])->visible(fn (Get $get) => $get('kind') === 'overhead' || ($get('kind') === 'labor' && $get('mode') === 'breakdown')),
                ])->action(fn (array $data) => $this->run('configure', ['type' => 'rate'] + $data));
    }

    public function recipeAction(): Action
    {
        return Action::make('recipe')->label('Guardar receta')->visible(fn () => $this->admin())
            ->fillForm(function (array $arguments): array {
                if (empty($arguments['production'])) {
                    return ['batch' => 1, 'materials' => [], 'setup_minutes' => 0, 'machine_minutes' => 0, 'manual_minutes' => 0, 'external' => 0];
                }
                $p = Production::findOrFail($arguments['production']);

                return ['item_id' => $p->item_id, 'batch' => $p->quantity, 'materials' => array_map(fn ($l) => ['item_id' => $l['item_id'], 'quantity' => $l['quantity']], $p->consumptions),
                    'printer_id' => $p->printer_id, 'activity' => $p->activity, 'setup_minutes' => 0, 'machine_minutes' => $p->machine_minutes, 'manual_minutes' => $p->manual_minutes, 'external' => ($p->cost_snapshot['external'] ?? 0) / 100];
            })->schema([
                $this->choice('item_id', 'Producto', fn () => $this->items('product')), $this->number('batch', 'Unidades del lote', 1, true)->minValue(1),
                $this->consumptionFields('materials'), $this->choice('printer_id', 'Máquina', fn () => Printer::pluck('name', 'id')->all(), false),
                $this->choice('activity', 'Actividad', self::activities()), $this->number('setup_minutes', 'Preparación fija por tanda (min)', 0, true),
                $this->number('machine_minutes', 'Fabricación variable por lote (min)', 0, true), $this->number('manual_minutes', 'Trabajo manual por lote (min)', 0, true),
                $this->number('external', 'Servicios externos por lote S/', 0),
            ])->action(fn (array $data) => $this->run('configure', ['type' => 'recipe'] + $data));
    }

    public function adjustAction(): Action
    {
        return Action::make('adjust')->label('Ajuste de existencias')->visible(fn () => $this->admin())->schema([
            $this->choice('item_id', 'Material / producto', fn () => Item::pluck('name', 'id')->all()),
            $this->choice('direction', 'Movimiento', ['in' => 'Entrada', 'out' => 'Salida'])->default('in'),
            $this->number('quantity', 'Cantidad en unidad de consumo', 1)->minValue(0.001), $this->number('amount', 'Valor total de la entrada S/', 0), $this->notes('reason', 'Motivo'),
        ])->action(fn (array $data) => $this->run('adjustStock', $data));
    }

    public function correctTaskAction(): Action
    {
        return Action::make('correctTask')->label('Corregir tiempo olvidado')->visible(fn () => $this->admin())->schema([
            $this->choice('task_id', 'Tarea pausada', fn () => Task::where('status', 'paused')->pluck('description', 'id')->all()),
            $this->number('manual_minutes', 'Minutos manuales', 0, true), $this->number('machine_minutes', 'Minutos máquina', 0, true), $this->notes('reason', 'Motivo'),
        ])->action(fn (array $data) => $this->run('correctTask', $data));
    }

    public function recalculateAction(): Action
    {
        return Action::make('recalculate')->label('Simular recálculo histórico')->visible(fn () => $this->admin())->schema([
            $this->choice('production_id', 'Producción', fn () => Production::latest('id')->limit(200)->get()->mapWithKeys(fn ($p) => [$p->id => '#'.$p->id.' '.$p->description])->all()), $this->notes('reason', 'Motivo'),
        ])->action(fn (array $data) => $this->run('recalculate', $data));
    }

    public function export()
    {
        abort_unless($this->permitted('reports'), 403);
        $rows = $this->reportData();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Indicador', 'Importe / valor'], ';', '"', '');
            foreach ($rows as $key => $value) {
                fputcsv($out, [$key, is_bool($value) ? (int) $value : $value], ';', '"', '');
            }
            fclose($out);
        }, 'dami3d-resultados-'.today()->format('Ymd').'.csv');
    }

    public function reportData(): array
    {
        abort_unless($this->permitted('reports'), 403);
        $period = fn ($q, string $column = 'created_at') => $q->when($this->from, fn ($q) => $q->whereDate($column, '>=', $this->from))
            ->when($this->until, fn ($q) => $q->whereDate($column, '<=', $this->until));
        $scope = DamiOrder::where('workshop', true)->where('document_kind', 'order')
            ->when($this->customerFilter, fn ($q) => $q->where('customer_id', $this->customerFilter))
            ->when($this->orderFilter, fn ($q) => $q->whereKey($this->orderFilter));
        $categoryItems = Item::when($this->categoryFilter, fn ($q) => $q->where('category', $this->categoryFilter))->pluck('id');
        if ($this->categoryFilter) {
            $scope->whereIn('id', OrderLine::whereIn('item_id', $categoryItems)->select('dami_order_id'));
        }
        if ($this->machine) {
            $scope->whereIn('id', Production::where('printer_id', $this->machine)->whereNotNull('dami_order_id')->select('dami_order_id'));
        }
        $ids = (clone $scope)->pluck('id');
        $dimension = (bool) ($this->customerFilter || $this->orderFilter || $this->categoryFilter || $this->machine);
        $orders = $period((clone $scope)->when($this->worker, fn ($q) => $q->where('created_by', $this->worker)))->get();
        $deliveries = $period(Delivery::whereIn('line_id', OrderLine::whereIn('dami_order_id', $ids)->select('id'))
            ->when($this->worker, fn ($q) => $q->where('user_id', $this->worker)))->get();
        $lines = OrderLine::whereIn('id', $deliveries->pluck('line_id'))->get()->keyBy('id');
        $recognized = $deliveries->sum(fn ($d) => $d->quantity * $lines[$d->line_id]->unit_cents);
        $purchases = $period(Purchase::where('review', '!=', 'void')->when($this->worker, fn ($q) => $q->where('user_id', $this->worker))
            ->when($this->customerFilter || $this->orderFilter || $this->machine, fn ($q) => $q->whereIn('dami_order_id', $ids)), 'purchased_on')->get();
        if ($this->categoryFilter) {
            $purchases = $purchases->filter(fn ($p) => $ids->contains($p->dami_order_id) || collect($p->lines)->contains(fn ($l) => $categoryItems->contains($l['item_id'])));
        }
        $produced = $period(Production::when($this->worker, fn ($q) => $q->where('user_id', $this->worker))
            ->when($this->machine, fn ($q) => $q->where('printer_id', $this->machine))
            ->when($this->categoryFilter, fn ($q) => $q->whereIn('item_id', $categoryItems))
            ->when($this->orderFilter || $this->customerFilter, fn ($q) => $q->whereIn('dami_order_id', $ids)))->get();
        $ledger = Ledger::when($this->worker, fn ($q) => $q->where('user_id', $this->worker))
            ->when($dimension, fn ($q) => $q->where(fn ($q) => $q->whereIn('dami_order_id', $ids)->orWhereIn('purchase_id', $purchases->pluck('id'))));
        $direct = $purchases->where('category', 'direct')->sum('amount_cents');
        $overhead = $purchases->where('category', 'overhead')->sum('amount_cents');
        $complete = $deliveries->every('cost_complete');
        $overheadSold = $deliveries->sum(fn ($d) => $d->cost_snapshot['overhead'] ?? 0);
        $assigned = $produced->sum(fn ($p) => $p->cost_snapshot['overhead'] ?? 0);
        $rates = Rate::where('kind', 'overhead')->whereDate('effective_on', '<=', today())->orderByDesc('effective_on')->orderByDesc('id')->get()->unique('name');
        $capacity = $rates->map(fn ($r) => (float) ($r->parameters['capacity'] ?? 0))->filter()->min();
        $manualHours = $produced->sum('manual_minutes') / 60;

        return ['Ventas registradas S/' => $orders->sum('sale_cents') / 100,
            'Pedidos pendientes' => (clone $scope)->where('delivery_status', '!=', 'delivered')->count(),
            'Pedidos atrasados' => (clone $scope)->where('delivery_status', '!=', 'delivered')->whereDate('delivery_date', '<', today())->count(),
            'Entregas próximas (7 días)' => (clone $scope)->where('delivery_status', '!=', 'delivered')->whereBetween('delivery_date', [today(), today()->addDays(7)])->count(),
            'Tareas en curso' => Task::where('status', 'running')->when($this->worker, fn ($q) => $q->where('responsible_id', $this->worker))->when($this->machine, fn ($q) => $q->where('printer_id', $this->machine))->count(),
            'Tareas con incidencias' => Task::whereNotNull('incident')->where('status', '!=', 'finished')->count(),
            'Materiales sin existencias' => Item::where('kind', 'material')->where('active', true)->where('quantity_milli', 0)->count(),
            'Ventas entregadas S/' => $recognized / 100,
            'Cobros netos del periodo S/' => $period((clone $ledger)->whereIn('kind', ['collection', 'collection_reversal']))->sum('amount_cents') / 100,
            'Por cobrar total S/' => (clone $scope)->selectRaw('SUM(sale_cents-collected_cents) AS balance')->value('balance') / 100,
            'Vencido S/' => (clone $scope)->whereDate('payment_due', '<', today())->selectRaw('SUM(sale_cents-collected_cents) AS balance')->value('balance') / 100,
            'Compras del periodo S/' => $purchases->sum('amount_cents') / 100,
            'Gastos generales reales S/' => $overhead / 100,
            'Gastos generales asignados a producción S/' => $produced->sum(fn ($p) => $p->cost_snapshot['overhead'] ?? 0) / 100,
            'Diferencia generales reales menos asignados S/' => ($overhead - $assigned) / 100,
            'Horas manuales registradas' => $manualHours,
            'Capacidad normal mensual configurada (h)' => $capacity ?? 'Sin configurar',
            'Capacidad mensual no usada (h; comparar un mes)' => $capacity === null ? 'Sin configurar' : max(0, $capacity - $manualHours),
            'Costo conocido de lo producido S/' => $produced->sum('cost_cents') / 100,
            'Costo conocido de lo entregado S/' => $deliveries->sum('cost_cents') / 100,
            'Resultado estándar de lo entregado S/' => $complete ? ($recognized - $deliveries->sum('cost_cents')) / 100 : 'Incompleto',
            'Resultado estimado con generales reales del periodo S/' => $complete ? ($recognized - $deliveries->sum('cost_cents') + $overheadSold - $overhead) / 100 : 'Incompleto',
            'Costos directos adicionales registrados S/' => $direct / 100,
            'Producciones con costo incompleto' => $produced->where('cost_complete', false)->count(),
            'Fondos por justificar o devolver S/' => Fund::where('status', 'open')->when($this->worker, fn ($q) => $q->where('user_id', $this->worker))->when($dimension, fn ($q) => $q->whereIn('dami_order_id', $ids))->sum('remaining_cents') / 100,
            'Reembolsos pendientes S/' => $purchases->where('payment_source', 'employee')->sum(fn ($p) => $p->amount_cents - $p->reimbursed_cents) / 100,
            'Cajas y cuentas saldo global actual S/' => Account::sum('balance_cents') / 100,
            'Entradas reales en cajas del periodo S/' => $period((clone $ledger)->whereNotNull('account_id')->where('amount_cents', '>', 0))->sum('amount_cents') / 100,
            'Salidas reales de cajas del periodo S/' => -$period((clone $ledger)->whereNotNull('account_id')->where('amount_cents', '<', 0))->sum('amount_cents') / 100,
        ];
    }

    protected function getViewData(): array
    {
        $allowed = ['orders', 'tasks', 'production', 'purchases', 'funds', 'customers', 'stock'];
        if ($this->admin()) {
            $allowed = [...$allowed, 'reports', 'settings', 'audit', 'cash'];
        }
        if (! in_array($this->tab, $allowed)) {
            $this->tab = 'orders';
        }
        $filter = fn ($q, string $column = 'created_at', string $workerColumn = 'user_id', ?string $orderColumn = 'dami_order_id') => $q
            ->when($this->from, fn ($q) => $q->whereDate($column, '>=', $this->from))->when($this->until, fn ($q) => $q->whereDate($column, '<=', $this->until))
            ->when($this->worker && $this->admin(), fn ($q) => $q->where($workerColumn, $this->worker))
            ->when($this->orderFilter && $orderColumn, fn ($q) => $q->where($orderColumn, $this->orderFilter));
        $data = ['admin' => $this->admin(), 'tabs' => $allowed, 'workers' => $this->workers(), 'machines' => Printer::pluck('name', 'id'), 'customersFilter' => Customer::limit(200)->pluck('name', 'id'), 'ordersFilter' => $this->orders()];
        $data['rows'] = match ($this->tab) {
            'orders' => $filter(DamiOrder::where('workshop', true), 'created_at', 'created_by', 'id')->when($this->customerFilter, fn ($q) => $q->where('customer_id', $this->customerFilter))
                ->where(fn ($q) => $q->where('order_number', 'like', '%'.$this->search.'%')->orWhere('client_name', 'like', '%'.$this->search.'%')->orWhere('description', 'like', '%'.$this->search.'%'))->latest()->limit(100)->get(),
            'tasks' => $filter(Task::query(), 'created_at', 'responsible_id')->when(! $this->admin(), fn ($q) => $q->where('responsible_id', auth()->id()))
                ->when($this->machine, fn ($q) => $q->where('printer_id', $this->machine))->latest()->limit(100)->get(),
            'production' => $filter(Production::query())->when($this->machine, fn ($q) => $q->where('printer_id', $this->machine))->latest('id')->limit(100)->get(),
            'purchases' => $filter(Purchase::query(), 'purchased_on')->when(! $this->admin(), fn ($q) => $q->where('user_id', auth()->id()))->latest()->limit(100)->get(),
            'funds' => Fund::when(! $this->admin(), fn ($q) => $q->where('user_id', auth()->id()))->latest()->limit(100)->get(),
            'customers' => Customer::where(fn ($q) => $q->where('name', 'like', '%'.$this->search.'%')->orWhere('phone', 'like', '%'.$this->search.'%')->orWhere('document', 'like', '%'.$this->search.'%'))->latest()->limit(100)->get(),
            'stock' => Item::where('name', 'like', '%'.$this->search.'%')->when($this->categoryFilter, fn ($q) => $q->where('category', $this->categoryFilter))->orderByDesc('favorite')->orderBy('name')->limit(100)->get(),
            'settings' => Rate::latest('id')->limit(100)->get(),
            'cash' => $filter(Ledger::query())->latest('id')->limit(100)->get(),
            'audit' => $filter(Audit::query(), 'created_at', 'user_id', null)->latest('id')->limit(100)->get(),
            default => collect(),
        };
        $data['report'] = $this->tab === 'reports' ? $this->reportData() : [];
        $data['accounts'] = $this->admin() ? Account::all() : collect();
        $data['corrections'] = $this->admin() && $this->tab === 'cash' ? Correction::where('status', 'pending')->get() : collect();
        $data['orderLines'] = $this->tab === 'orders' ? OrderLine::whereIn('dami_order_id', $data['rows']->pluck('id'))->get()->groupBy('dami_order_id') : collect();
        $data['costs'] = $this->admin() && $this->tab === 'orders' ? Production::whereIn('dami_order_id', $data['rows']->pluck('id'))->get()->groupBy('dami_order_id') : collect();
        $data['fundMovements'] = $this->tab === 'funds' ? Ledger::whereIn('fund_id', $data['rows']->pluck('id'))->get()->groupBy('fund_id') : collect();
        $data['directCosts'] = $this->admin() && $this->tab === 'orders' ? Purchase::whereIn('dami_order_id', $data['rows']->pluck('id'))->where('category', 'direct')->where('review', '!=', 'void')->get()->groupBy('dami_order_id') : collect();
        $data['deliveredCosts'] = $this->admin() && $this->tab === 'orders' ? Delivery::join('workshop_order_lines','workshop_order_lines.id','=','workshop_deliveries.line_id')
            ->whereIn('workshop_order_lines.dami_order_id',$data['rows']->pluck('id'))->get(['workshop_deliveries.*', 'workshop_order_lines.dami_order_id'])->groupBy('dami_order_id') : collect();
        $data['productionTotals'] = $this->tab === 'production' ? $filter(Production::where('final_stage',true))->when($this->machine,fn ($q) => $q->where('printer_id',$this->machine))
            ->selectRaw('item_id, description, SUM(quantity) AS finished_quantity')->groupBy('item_id','description')->get() : collect();

        return $data;
    }
}
