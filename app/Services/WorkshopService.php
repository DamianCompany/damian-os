<?php

namespace App\Services;

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
use App\Models\Workshop\Operation;
use App\Models\Workshop\OrderLine;
use App\Models\Workshop\PaymentMethod;
use App\Models\Workshop\Production;
use App\Models\Workshop\Purchase;
use App\Models\Workshop\Rate;
use App\Models\Workshop\Stock;
use App\Models\Workshop\Task;
use App\Support\WorkshopNumber as N;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WorkshopService
{
    private User $actor;

    public function execute(User $actor, string $action, array $data, string $token): array
    {
        $permission = match ($action) {
            'configure', 'customer', 'sale', 'collect', 'purchase', 'production', 'task', 'deliver' => $action,
            'assignFund' => 'assign_fund', 'reviewPurchase', 'closeFund' => 'review',
            'reverse', 'adjustStock', 'correctTask', 'recalculate', 'voidPurchase', 'cancelOrder' => 'reverse',
            'returnFund' => 'purchase', 'payPurchase' => 'collect', 'transition' => 'production',
            'convert', 'approveDesign', 'cancelQuote', 'reviseQuote' => 'sale', 'requestCorrection' => 'collect',
            'updateCustomer' => 'customer', 'supplier' => 'purchase',
            'batch' => 'task',
            default => throw ValidationException::withMessages(['action' => 'Acción no válida.']),
        };
        abort_unless($actor->canWorkshop($permission), 403);
        Validator::make(['token' => $token], ['token' => 'required|uuid'])->validate();
        $this->actor = $actor;
        $fingerprint = hash('sha256', json_encode($data));

        return DB::transaction(function () use ($actor, $action, $data, $token, $fingerprint) {
            // Serializes requests from one account; domain rows also lock across accounts.
            User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $existing = Operation::find($token);
            if ($existing) {
                abort_unless($existing->user_id === $actor->id && $existing->action === $action && $existing->fingerprint === $fingerprint, 409);

                return $existing->result;
            }
            Operation::create(['token' => $token, 'user_id' => $actor->id, 'action' => $action, 'fingerprint' => $fingerprint, 'created_at' => now()]);
            $result = $this->{$action}($data);
            Audit::create(['user_id' => $actor->id, 'action' => $action, 'subject' => $result['subject'] ?? $action,
                'changes' => ['input' => $data, 'result' => $result], 'created_at' => now()]);
            Operation::whereKey($token)->update(['result' => json_encode($result)]);

            return $result;
        }, 3);
    }

    private function valid(array $data, array $rules): array
    {
        return Validator::make($data, $rules)->validate();
    }

    private function check(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['operation' => $message]);
        }
    }

    private function positive(mixed $value): int
    {
        $amount = N::scaled($value);
        $this->check($amount > 0, 'El importe debe ser mayor que cero.');

        return $amount;
    }

    private function result(string $type, int $id): array
    {
        return ['id' => $id, 'subject' => "$type:$id"];
    }

    private function customer(array $d): array
    {
        $d = $this->valid($d, ['name' => 'required|string|max:255', 'type' => 'required|in:person,institution',
            'phone' => 'nullable|string|max:60', 'document' => 'nullable|string|max:60', 'address' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:255', 'payment_terms' => 'nullable|string|max:255']);
        $duplicate = Customer::where('name', trim($d['name']))->exists()
            || (! empty($d['document']) && Customer::where('document', $d['document'])->exists());
        $customer = Customer::create($d + ['created_by' => $this->actor->id]);

        return $this->result('customer', $customer->id) + ['duplicate_warning' => $duplicate];
    }

    private function updateCustomer(array $d): array
    {
        $fields = $this->valid($d, ['name' => 'required|string|max:255', 'type' => 'required|in:person,institution',
            'phone' => 'nullable|string|max:60', 'document' => 'nullable|string|max:60', 'address' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:255', 'payment_terms' => 'nullable|string|max:255', 'version' => 'required|integer']);
        $c = Customer::lockForUpdate()->findOrFail($d['customer_id']);
        $this->check((int) $fields['version'] === $c->version, 'El cliente cambió en otro equipo. Recarga para no sobrescribirlo.');
        $fields['version']++;
        $c->update($fields);

        return $this->result('customer', $c->id);
    }

    private function supplier(array $d): array
    {
        return $this->configure(['type' => 'supplier'] + $d);
    }

    private function configure(array $d): array
    {
        $this->valid($d, ['type' => 'required|in:item,recipe,account,method,rate,machine,supplier,activity']);
        switch ($d['type']) {
            case 'activity':
                $this->valid($d, ['name' => 'required|string|max:100']);

                return $this->result('activity', Activity::firstOrCreate(['name' => $d['name']])->id);
            case 'item':
                $fields = $this->valid($d, ['name' => 'required|string|max:255', 'kind' => 'required|in:material,product',
                    'category' => 'nullable|string|max:255', 'unit' => 'required|string|max:40', 'purchase_unit' => 'required|string|max:40',
                    'conversion' => 'required|numeric|min:0.001|max:10000', 'price' => 'nullable|numeric|min:0', 'favorite' => 'boolean']);
                $item = Item::create(['name' => $fields['name'], 'kind' => $fields['kind'], 'category' => $fields['category'] ?? null,
                    'unit' => $fields['unit'], 'purchase_unit' => $fields['purchase_unit'], 'conversion_milli' => N::scaled($fields['conversion'], 3),
                    'sale_price_cents' => isset($fields['price']) ? N::scaled($fields['price']) : null, 'favorite' => $fields['favorite'] ?? false]);

                return $this->result('item', $item->id);
            case 'recipe':
                $this->valid($d, ['item_id' => 'required|exists:workshop_items,id', 'batch' => 'required|integer|min:1|max:100000',
                    'materials' => 'present|array', 'materials.*.item_id' => 'required|exists:workshop_items,id',
                    'materials.*.quantity' => 'required|numeric|min:0.001|max:10000',
                    'printer_id' => 'nullable|exists:printers,id', 'setup_minutes' => 'required|integer|min:0|max:100000',
                    'machine_minutes' => 'required|integer|min:0|max:100000', 'manual_minutes' => 'required|integer|min:0|max:100000',
                    'activity' => 'required|string|max:100', 'external' => 'required|numeric|min:0']);
                $item = Item::lockForUpdate()->findOrFail($d['item_id']);
                $this->check($item->kind === 'product', 'Las recetas corresponden a productos terminados.');
                foreach ($d['materials'] as $line) {
                    $this->check(Item::findOrFail($line['item_id'])->kind === 'material', 'Usa materiales en la receta.');
                }
                $item->update(['recipe' => collect($d)->only(['materials', 'printer_id', 'setup_minutes', 'machine_minutes', 'manual_minutes', 'activity', 'external'])->all(),
                    'recipe_batch' => $d['batch'], 'version' => $item->version + 1]);

                return $this->result('recipe', $item->id);
            case 'machine':
                $this->valid($d, ['name' => 'required|string|max:255', 'location' => 'required|string|max:255']);

                return $this->result('machine', Printer::create(['name' => $d['name'], 'location' => $d['location'], 'status' => 'available', 'is_active' => true])->id);
            case 'supplier':
                $this->valid($d, ['name' => 'required|string|max:255', 'phone' => 'nullable|string|max:30']);

                return $this->result('supplier', ProveedorDami3d::create(['razon_social' => $d['name'], 'tipo' => 'empresa', 'telefono' => $d['phone'] ?? null,
                    'estado' => 'activo', 'creado_por' => $this->actor->id])->id);
            case 'account':
                $this->valid($d, ['name' => 'required|string|max:255', 'opening' => 'required|numeric|min:0']);
                $account = Account::create(['name' => $d['name']]);
                $opening = N::scaled($d['opening']);
                if ($opening) {
                    $this->money($account->id, $opening, 'opening', [], 'Saldo inicial verificado');
                }

                return $this->result('account', $account->id);
            case 'method':
                $this->valid($d, ['name' => 'required|string|max:255']);

                return $this->result('method', PaymentMethod::firstOrCreate(['name' => $d['name']])->id);
            default:
                return $this->saveRate($d);
        }
    }

    private function saveRate(array $d): array
    {
        $this->valid($d, ['kind' => 'required|in:machine,labor,overhead', 'name' => 'required|string|max:255',
            'mode' => 'required|in:manual,breakdown', 'effective_on' => 'required|date',
            'activity' => 'nullable|string|max:100', 'printer_id' => 'nullable|exists:printers,id', 'user_id' => 'nullable|exists:users,id']);
        foreach (['hour_rate', 'power_kw', 'electricity_rate', 'depreciable', 'life_hours', 'maintenance', 'consumables', 'monthly', 'capacity', 'business_percent'] as $field) {
            if (isset($d[$field]) && $d[$field] !== '') {
                $this->valid($d, [$field => 'numeric|min:0|max:10000000']);
            }
        }
        $hour = null;
        if ($d['kind'] === 'machine') {
            $this->check(! empty($d['printer_id']), 'Selecciona una máquina.');
            if ($d['mode'] === 'manual') {
                $hour = N::scaled($d['hour_rate'] ?? '');
            } else {
                $life = N::scaled($d['life_hours'] ?? '0', 3);
                $this->check($life > 0, 'La vida útil debe ser mayor que cero.');
                $electricity = N::proportional(N::scaled($d['electricity_rate'] ?? '0', 4), N::scaled($d['power_kw'] ?? '0', 3), 100000);
                $hour = $electricity + N::proportional(N::scaled($d['depreciable'] ?? '0'), 1000, $life)
                    + N::scaled($d['maintenance'] ?? '0') + N::scaled($d['consumables'] ?? '0');
            }
        } elseif ($d['kind'] === 'labor' && $d['mode'] === 'manual') {
            $hour = N::scaled($d['hour_rate'] ?? '');
        } else {
            $capacity = N::scaled($d['capacity'] ?? '0', 3);
            $this->check($capacity > 0, 'La capacidad normal debe ser mayor que cero.');
            $monthly = N::scaled($d['monthly'] ?? '');
            if ($d['kind'] === 'overhead') {
                $percent = N::scaled($d['business_percent'] ?? '100');
                $this->check($percent <= 10000, 'El porcentaje no puede superar 100%.');
                $monthly = N::proportional($monthly, $percent, 10000);
            }
            $hour = N::proportional($monthly, 1000, $capacity);
        }
        $rate = Rate::create(['kind' => $d['kind'], 'name' => $d['name'], 'activity' => $d['activity'] ?? null,
            'printer_id' => $d['printer_id'] ?? null, 'user_id' => $d['user_id'] ?? null, 'hour_cents' => $hour,
            'parameters' => collect($d)->except('type')->all(), 'effective_on' => $d['effective_on'], 'created_by' => $this->actor->id, 'created_at' => now()]);

        return $this->result('rate', $rate->id);
    }

    private function sale(array $d): array
    {
        $this->valid($d, ['customer_id' => 'nullable|exists:workshop_customers,id', 'kind' => 'required|in:quote,custom,stock',
            'lines' => 'required|array|min:1|max:30', 'lines.*.description' => 'required|string|max:255',
            'lines.*.quantity' => 'required|integer|min:1|max:100000', 'lines.*.price' => 'required|numeric|min:0|max:10000000',
            'lines.*.item_id' => 'nullable|exists:workshop_items,id', 'received' => 'required|numeric|min:0',
            'delivery_date' => 'required|date', 'payment_due' => 'nullable|date', 'design_required' => 'boolean',
            'purchase_reference' => 'nullable|string|max:255', 'delivery_address' => 'nullable|string|max:255', 'notes' => 'nullable|string|max:10000',
            'attachments' => 'nullable|array|max:5', 'attachments.*' => 'string|max:255', 'immediate' => 'boolean']);
        $total = 0;
        foreach ($d['lines'] as $line) {
            $total += N::scaled($line['price']) * $line['quantity'];
        }
        $this->check($total <= 100_000_000_000, 'El total supera el límite permitido.');
        $received = N::scaled($d['received']);
        $this->check($received <= $total && ($d['kind'] !== 'quote' || $received === 0), 'Revisa el cobro: no se cobra una cotización ni más que su total.');
        $customer = empty($d['customer_id']) ? null : Customer::findOrFail($d['customer_id']);
        $order = DamiOrder::create([
            'workshop' => true, 'order_number' => 'D3D-'.now()->format('ymd').'-'.strtoupper(Str::random(12)),
            'customer_id' => $customer?->id, 'client_name' => $customer?->name ?? 'Cliente ocasional', 'client_document' => $customer?->document,
            'document_kind' => $d['kind'] === 'quote' ? 'quote' : 'order', 'quantity' => array_sum(array_column($d['lines'], 'quantity')),
            'description' => implode(' / ', array_column($d['lines'], 'description')), 'sale_cents' => $total,
            'unit_sale_price' => 0, 'total_price' => $total / 100, 'advance' => 0, 'pending_balance' => $total / 100,
            'start_date' => today(), 'end_date' => $d['delivery_date'], 'delivery_date' => $d['delivery_date'],
            'filament_grams' => 0, 'filament_type' => 'PLA', 'print_minutes' => 0,
            'responsible_name' => $this->actor->name, 'printer_id' => null, 'printer_location' => '',
            'created_by' => $this->actor->id, 'status' => 'pending', 'payment_status' => $total === 0 ? 'paid' : 'pending',
            'design_required' => $d['design_required'] ?? false, 'payment_due' => $d['payment_due'] ?? null,
            'purchase_reference' => $d['purchase_reference'] ?? null, 'delivery_address' => $d['delivery_address'] ?? null,
            'notes' => $d['notes'] ?? null, 'attachments' => $this->paths($d['attachments'] ?? []),
        ]);
        foreach ($d['lines'] as $line) {
            $item = empty($line['item_id']) ? null : Item::findOrFail($line['item_id']);
            $this->check(! $item || $item->kind === 'product', 'Selecciona un producto terminado.');
            OrderLine::create(['dami_order_id' => $order->id, 'item_id' => $item?->id, 'description' => $line['description'],
                'quantity' => $line['quantity'], 'unit_cents' => N::scaled($line['price']),
                'recipe_snapshot' => $item?->recipe ? ['recipe' => $item->recipe, 'batch' => $item->recipe_batch, 'version' => $item->version,
                    'estimate' => $this->estimateRecipe($item, $line['quantity'], $this->actor->id)] : null]);
        }
        if ($received > 0) {
            $this->collect($d + ['order_id' => $order->id, 'amount' => $d['received']]);
        }
        if (! empty($d['immediate'])) {
            $this->check($d['kind'] === 'stock', 'La entrega inmediata requiere venta de producto terminado.');
            foreach (OrderLine::where('dami_order_id', $order->id)->get() as $line) {
                $this->deliver(['line_id' => $line->id, 'quantity' => $line->quantity, 'source' => 'stock', 'mode' => 'immediate']);
            }
        }

        return $this->result('order', $order->id);
    }

    private function paths(array $paths): array
    {
        foreach ($paths as $path) {
            $this->check(is_string($path) && str_starts_with($path, 'workshop/') && ! str_contains($path, '..')
                && Storage::disk('local')->exists($path), 'Archivo no válido o ausente.');
        }

        return $paths;
    }

    private function lockedOrder(int $id): DamiOrder
    {
        $order = DamiOrder::where('workshop', true)->lockForUpdate()->findOrFail($id);
        $this->check($order->document_kind === 'order', 'Primero convierte la cotización en pedido.');

        return $order;
    }

    private function convert(array $d): array
    {
        $order = DamiOrder::where('workshop', true)->lockForUpdate()->findOrFail($d['order_id']);
        $this->check($order->document_kind === 'quote', 'La cotización ya se convirtió.');
        $order->update(['document_kind' => 'order', 'version' => $order->version + 1]);

        return $this->result('order', $order->id);
    }

    private function reviseQuote(array $d): array
    {
        $this->valid($d, ['order_id' => 'required|exists:dami_orders,id', 'version' => 'required|integer',
            'lines' => 'required|array|min:1|max:30', 'lines.*.description' => 'required|string|max:255', 'lines.*.quantity' => 'required|integer|min:1|max:100000',
            'lines.*.price' => 'required|numeric|min:0|max:10000000', 'lines.*.item_id' => 'nullable|exists:workshop_items,id']);
        $order = DamiOrder::where('workshop', true)->lockForUpdate()->findOrFail($d['order_id']);
        $this->check($order->document_kind === 'quote', 'Un pedido confirmado no se modifica desde cotizaciones. Duplica o solicita una corrección.');
        $this->check($order->version === (int) $d['version'], 'La cotización cambió en otro equipo. Recarga antes de guardar.');
        $before = OrderLine::where('dami_order_id', $order->id)->get()->toArray();
        OrderLine::where('dami_order_id', $order->id)->update(['superseded' => true]);
        $total = 0;
        foreach ($d['lines'] as $line) {
            $item = empty($line['item_id']) ? null : Item::findOrFail($line['item_id']);
            $this->check(! $item || $item->kind === 'product', 'Selecciona un producto terminado.');
            $price = N::scaled($line['price']);
            $total += $price * $line['quantity'];
            OrderLine::create(['dami_order_id' => $order->id, 'item_id' => $item?->id, 'description' => $line['description'], 'quantity' => $line['quantity'], 'unit_cents' => $price,
                'recipe_snapshot' => $item?->recipe ? ['recipe' => $item->recipe, 'batch' => $item->recipe_batch, 'version' => $item->version, 'estimate' => $this->estimateRecipe($item, $line['quantity'], $this->actor->id)] : null]);
        }
        $order->update(['sale_cents' => $total, 'total_price' => $total / 100, 'pending_balance' => $total / 100, 'quantity' => array_sum(array_column($d['lines'], 'quantity')),
            'description' => implode(' / ', array_column($d['lines'], 'description')), 'version' => $order->version + 1]);

        return $this->result('order', $order->id) + ['previous_lines' => $before, 'new_total_cents' => $total];
    }

    private function approveDesign(array $d): array
    {
        $order = $this->lockedOrder($d['order_id']);
        $order->update(['design_approved' => true, 'version' => $order->version + 1]);

        return $this->result('order', $order->id);
    }

    private function cancelQuote(array $d): array
    {
        $order = DamiOrder::where('workshop', true)->lockForUpdate()->findOrFail($d['order_id']);
        $this->check($order->document_kind === 'quote', 'Solo una cotización sin confirmar se puede anular aquí.');
        $order->update(['document_kind' => 'cancelled', 'version' => $order->version + 1]);

        return $this->result('order', $order->id);
    }

    private function cancelOrder(array $d): array
    {
        $this->valid($d, ['reason' => 'required|string|min:5|max:1000']);
        $order = $this->lockedOrder($d['order_id']);
        $this->check($order->collected_cents === 0 && ! Production::where('dami_order_id', $order->id)->exists()
            && ! Delivery::whereIn('line_id', OrderLine::where('dami_order_id', $order->id)->select('id'))->exists()
            && ! Task::where('dami_order_id', $order->id)->where('status', 'running')->exists(),
            'Primero revierte cobros y concilia producción/entregas. No se anula un pedido ya ejecutado automáticamente.');
        $order->update(['document_kind' => 'cancelled', 'version' => $order->version + 1]);
        Task::where('dami_order_id', $order->id)->update(['status' => 'cancelled', 'started_at' => null, 'manual_started_at' => null]);

        return $this->result('order', $order->id) + ['reason' => $d['reason']];
    }

    private function money(?int $accountId, int $amount, string $kind, array $links = [], ?string $reason = null): Ledger
    {
        if ($accountId) {
            $account = Account::where('active', true)->lockForUpdate()->findOrFail($accountId);
            $this->check($account->balance_cents + $amount >= 0, 'Saldo insuficiente en la caja o cuenta.');
            $account->update(['balance_cents' => $account->balance_cents + $amount]);
        }

        return Ledger::create($links + ['account_id' => $accountId, 'amount_cents' => $amount, 'kind' => $kind,
            'user_id' => $this->actor->id, 'reason' => $reason, 'created_at' => now()]);
    }

    private function paymentFields(array $d): void
    {
        $this->valid($d, ['account_id' => 'required|exists:workshop_accounts,id', 'method_id' => 'required|exists:workshop_methods,id']);
    }

    private function collect(array $d): array
    {
        abort_unless($this->actor->canWorkshop('collect'), 403);
        $this->paymentFields($d);
        $order = $this->lockedOrder($d['order_id']);
        $amount = $this->positive($d['amount']);
        $this->check($order->collected_cents + $amount <= $order->sale_cents, 'El cobro supera el saldo del pedido.');
        $entry = $this->money($d['account_id'], $amount, 'collection', ['dami_order_id' => $order->id, 'method_id' => $d['method_id']]);
        $order->update(['collected_cents' => $order->collected_cents + $amount,
            'advance' => ($order->collected_cents + $amount) / 100, 'pending_balance' => ($order->sale_cents - $order->collected_cents - $amount) / 100,
            'payment_status' => $order->collected_cents + $amount === $order->sale_cents ? 'paid' : 'partial', 'version' => $order->version + 1]);

        return $this->result('ledger', $entry->id);
    }

    private function moveStock(Item $item, int $quantity, ?int $value, string $kind, string $reference, ?string $reason = null, int $overhead = 0): array
    {
        $this->check($item->quantity_milli + $quantity >= 0, 'Stock insuficiente de '.$item->name.'.');
        $complete = (bool) $item->valuation_complete;
        if ($quantity < 0) {
            $value = -N::proportional((int) $item->value_cents, -$quantity, (int) $item->quantity_milli);
            $overhead = -N::proportional((int) $item->overhead_value_cents, -$quantity, (int) $item->quantity_milli);
        } else {
            $complete = ($item->quantity_milli === 0 || $complete) && $value !== null;
        }
        $item->update(['quantity_milli' => $item->quantity_milli + $quantity, 'value_cents' => $item->value_cents + ($value ?? 0),
            'valuation_complete' => $complete, 'overhead_value_cents' => $item->overhead_value_cents + $overhead]);
        Stock::create(['item_id' => $item->id, 'user_id' => $this->actor->id, 'kind' => $kind, 'quantity_milli' => $quantity,
            'value_cents' => $value ?? 0, 'overhead_value_cents' => $overhead, 'reference' => $reference, 'reason' => $reason, 'created_at' => now()]);

        return ['cents' => abs($value ?? 0), 'complete' => $complete, 'overhead' => abs($overhead)];
    }

    private function purchase(array $d): array
    {
        $this->valid($d, ['description' => 'required|string|max:255', 'category' => 'required|in:inventory,direct,overhead',
            'source' => 'required|in:account,fund,employee,credit', 'supplier_id' => 'nullable|exists:proveedores_dami3d,id',
            'order_id' => 'nullable|exists:dami_orders,id', 'amount' => 'required|numeric|min:0.01|max:10000000',
            'purchased_on' => 'required|date|before_or_equal:today', 'receipt' => 'nullable|string',
            'lines' => 'present|array|max:30', 'lines.*.item_id' => 'required|exists:workshop_items,id',
            'lines.*.quantity' => 'required|numeric|min:0.001|max:10000', 'lines.*.amount' => 'required|numeric|min:0.01']);
        $amount = $this->positive($d['amount']);
        $this->check(($d['category'] === 'inventory') === ! empty($d['lines']), 'Para materiales/productos detalla las líneas; para gastos deja el detalle vacío.');
        if (! empty($d['order_id'])) {
            $this->lockedOrder($d['order_id']);
        }
        $this->check($d['category'] !== 'direct' || ! empty($d['order_id']), 'Asocia el costo directo a un pedido.');
        $sum = array_sum(array_map(fn ($l) => N::scaled($l['amount']), $d['lines']));
        $this->check(! $d['lines'] || $sum === $amount, 'Los importes del detalle deben sumar el total de la compra.');
        $fund = null;
        if ($d['source'] === 'fund') {
            $fund = Fund::lockForUpdate()->findOrFail($d['fund_id'] ?? 0);
            abort_unless($fund->user_id === $this->actor->id, 403);
            $this->check($fund->status === 'open' && $fund->remaining_cents >= $amount, 'Fondo cerrado o saldo insuficiente.');
            $fund->update(['remaining_cents' => $fund->remaining_cents - $amount]);
        }
        if ($d['source'] === 'account') {
            $this->paymentFields($d);
        }
        $purchase = Purchase::create(['supplier_id' => $d['supplier_id'] ?? null, 'dami_order_id' => $d['order_id'] ?? null,
            'fund_id' => $fund?->id, 'user_id' => $this->actor->id, 'description' => $d['description'], 'category' => $d['category'],
            'payment_source' => $d['source'], 'amount_cents' => $amount, 'paid_cents' => $d['source'] === 'credit' ? 0 : $amount,
            'purchased_on' => $d['purchased_on'], 'lines' => $d['lines'], 'receipt' => empty($d['receipt']) ? null : $this->paths([$d['receipt']])[0]]);
        if ($d['source'] === 'account') {
            $this->money($d['account_id'], -$amount, 'purchase_payment', ['purchase_id' => $purchase->id, 'method_id' => $d['method_id']]);
        } elseif ($fund) {
            $this->money(null, -$amount, 'fund_expense', ['purchase_id' => $purchase->id, 'fund_id' => $fund->id]);
        }
        // Lock items in a stable order across purchases/consumptions.
        foreach (collect($d['lines'])->sortBy('item_id') as $line) {
            $item = Item::lockForUpdate()->findOrFail($line['item_id']);
            $qty = N::proportional(N::scaled($line['quantity'], 3), (int) $item->conversion_milli, 1000);
            $this->check($qty > 0, 'La conversión debe producir una cantidad positiva.');
            $this->moveStock($item, $qty, N::scaled($line['amount']), 'purchase', 'purchase:'.$purchase->id);
        }

        return $this->result('purchase', $purchase->id);
    }

    private function payPurchase(array $d): array
    {
        $this->paymentFields($d);
        $purchase = Purchase::lockForUpdate()->findOrFail($d['purchase_id']);
        $this->check($purchase->review !== 'void', 'La compra está anulada.');
        abort_unless($this->actor->isWorkshopAdmin() || $purchase->user_id === $this->actor->id, 403);
        $this->check(in_array($purchase->payment_source, ['credit', 'employee']), 'Esta compra ya fue pagada desde su origen.');
        $this->check($this->actor->isWorkshopAdmin(), 'Solo el administrador paga deudas y autoriza reembolsos.');
        $field = $purchase->payment_source === 'employee' ? 'reimbursed_cents' : 'paid_cents';
        $amount = $this->positive($d['amount']);
        $this->check($purchase->$field + $amount <= $purchase->amount_cents, 'El pago supera el saldo pendiente.');
        $entry = $this->money($d['account_id'], -$amount, $field === 'reimbursed_cents' ? 'reimbursement' : 'purchase_payment',
            ['purchase_id' => $purchase->id, 'method_id' => $d['method_id']]);
        $purchase->update([$field => $purchase->$field + $amount]);

        return $this->result('ledger', $entry->id);
    }

    private function assignFund(array $d): array
    {
        $this->valid($d, ['user_id' => 'required|exists:users,id', 'purpose' => 'required|string|max:255', 'order_id' => 'nullable|exists:dami_orders,id']);
        $this->paymentFields($d);
        $this->check(User::findOrFail($d['user_id'])->canWorkshop('purchase'), 'El trabajador no tiene acceso a compras.');
        $amount = $this->positive($d['amount']);
        $fund = Fund::create(['user_id' => $d['user_id'], 'account_id' => $d['account_id'], 'purpose' => $d['purpose'],
            'dami_order_id' => $d['order_id'] ?? null, 'assigned_cents' => $amount, 'remaining_cents' => $amount, 'created_by' => $this->actor->id]);
        $this->money($d['account_id'], -$amount, 'fund_transfer', ['fund_id' => $fund->id, 'method_id' => $d['method_id']]);

        return $this->result('fund', $fund->id);
    }

    private function returnFund(array $d): array
    {
        $this->paymentFields($d);
        $fund = Fund::lockForUpdate()->findOrFail($d['fund_id']);
        abort_unless($this->actor->isWorkshopAdmin() || $fund->user_id === $this->actor->id, 403);
        $amount = $this->positive($d['amount']);
        $this->check($fund->status === 'open' && $amount <= $fund->remaining_cents, 'La devolución supera el saldo del fondo.');
        $fund->update(['remaining_cents' => $fund->remaining_cents - $amount]);
        $entry = $this->money($d['account_id'], $amount, 'fund_return', ['fund_id' => $fund->id, 'method_id' => $d['method_id']]);

        return $this->result('ledger', $entry->id);
    }

    private function reviewPurchase(array $d): array
    {
        $this->valid($d, ['review' => 'required|in:approved,observed', 'note' => 'nullable|string|max:500']);
        $purchase = Purchase::lockForUpdate()->findOrFail($d['purchase_id']);
        $this->check($purchase->review !== 'void', 'Una compra anulada no se vuelve a aprobar.');
        $this->check($d['review'] !== 'observed' || ! empty($d['note']), 'Indica la observación.');
        $purchase->update(['review' => $d['review'], 'review_note' => $d['note'] ?? null]);

        return $this->result('purchase', $purchase->id);
    }

    private function closeFund(array $d): array
    {
        $fund = Fund::lockForUpdate()->findOrFail($d['fund_id']);
        $this->check($fund->remaining_cents === 0 && ! Purchase::where('fund_id', $fund->id)->whereNotIn('review', ['approved', 'void'])->exists(),
            'Primero aprobar los gastos y devolver o justificar todo el saldo.');
        $fund->update(['status' => 'closed']);

        return $this->result('fund', $fund->id);
    }

    private function requestCorrection(array $d): array
    {
        $this->valid($d, ['reason' => 'required|string|min:5|max:1000']);
        $entry = Ledger::findOrFail($d['ledger_id']);
        abort_unless($entry->user_id === $this->actor->id || $this->actor->isWorkshopAdmin(), 403);
        $correction = Correction::create(['user_id' => $this->actor->id, 'ledger_id' => $entry->id, 'reason' => $d['reason']]);

        return $this->result('correction', $correction->id);
    }

    private function reverse(array $d): array
    {
        $this->valid($d, ['reason' => 'required|string|min:5|max:1000']);
        $original = Ledger::lockForUpdate()->findOrFail($d['ledger_id']);
        $this->check(! $original->reversal_of && ! Ledger::where('reversal_of', $original->id)->exists(), 'El movimiento ya se revirtió.');
        if (in_array($original->kind, ['purchase_payment', 'reimbursement'])) {
            $purchase = Purchase::lockForUpdate()->findOrFail($original->purchase_id);
            $field = $original->kind === 'reimbursement' ? 'reimbursed_cents' : 'paid_cents';
            $amount = abs((int) $original->amount_cents);
            $this->check($amount <= $purchase->$field, 'El saldo ya cambió. Revisa la compra.');
            $entry = $this->money($original->account_id, $amount, $original->kind.'_reversal', ['purchase_id' => $purchase->id, 'method_id' => $original->method_id, 'reversal_of' => $original->id], $d['reason']);
            $changes = [$field => $purchase->$field - $amount];
            if ($purchase->payment_source === 'account') {
                $changes['payment_source'] = 'credit';
            }
            $purchase->update($changes);
            Correction::where('ledger_id', $original->id)->where('status', 'pending')->update(['status' => 'approved']);

            return $this->result('ledger', $entry->id) + ['original' => $original->toArray(), 'purchase_changes' => $changes];
        }
        if (in_array($original->kind, ['fund_transfer', 'fund_return'])) {
            $fund = Fund::lockForUpdate()->findOrFail($original->fund_id);
            if ($original->kind === 'fund_transfer') {
                $this->check($fund->status === 'open' && $fund->remaining_cents === $fund->assigned_cents && ! Ledger::where('fund_id', $fund->id)->where('id', '!=', $original->id)->exists(), 'El fondo ya tuvo movimientos; revisa y liquida su saldo.');
                $fund->update(['remaining_cents' => 0, 'status' => 'cancelled']);
            } else {
                $fund->update(['remaining_cents' => $fund->remaining_cents + $original->amount_cents, 'status' => 'open']);
            }
            $entry = $this->money($original->account_id, -$original->amount_cents, $original->kind.'_reversal', ['fund_id' => $fund->id, 'method_id' => $original->method_id, 'reversal_of' => $original->id], $d['reason']);
            Correction::where('ledger_id', $original->id)->where('status', 'pending')->update(['status' => 'approved']);

            return $this->result('ledger', $entry->id) + ['original' => $original->toArray()];
        }
        $this->check($original->kind === 'collection', 'Ese tipo de movimiento requiere conciliación de inventario; no se elimina automáticamente.');
        $order = $this->lockedOrder($original->dami_order_id);
        $entry = $this->money($original->account_id, -$original->amount_cents, 'collection_reversal',
            ['dami_order_id' => $order->id, 'method_id' => $original->method_id, 'reversal_of' => $original->id], $d['reason']);
        $collected = $order->collected_cents - $original->amount_cents;
        $order->update(['collected_cents' => $collected, 'advance' => $collected / 100, 'pending_balance' => ($order->sale_cents - $collected) / 100,
            'payment_status' => $collected ? 'partial' : 'pending', 'version' => $order->version + 1]);
        Correction::where('ledger_id', $original->id)->where('status', 'pending')->update(['status' => 'approved']);

        return $this->result('ledger', $entry->id);
    }

    private function adjustStock(array $d): array
    {
        $this->valid($d, ['direction' => 'required|in:in,out', 'reason' => 'required|string|min:5|max:1000', 'quantity' => 'required|numeric|min:0.001|max:10000']);
        $item = Item::lockForUpdate()->findOrFail($d['item_id']);
        $qty = N::scaled($d['quantity'], 3) * ($d['direction'] === 'in' ? 1 : -1);
        $this->moveStock($item, $qty, $qty > 0 ? N::scaled($d['amount'] ?? '0') : null, 'adjustment', 'admin:'.$this->actor->id, $d['reason']);

        return $this->result('item', $item->id);
    }

    private function voidPurchase(array $d): array
    {
        $this->valid($d, ['reason' => 'required|string|min:5|max:1000', 'refund_confirmed' => 'required|accepted']);
        $purchase = Purchase::lockForUpdate()->findOrFail($d['purchase_id']);
        $this->check($purchase->review !== 'void', 'La compra ya fue anulada.');
        if ($purchase->dami_order_id) {
            $this->check(! Delivery::whereIn('line_id', OrderLine::where('dami_order_id', $purchase->dami_order_id)->select('id'))->exists(),
                'La compra ya puede estar incluida en entregas. Concilia el costo antes de anularla.');
        }
        $stocks = Stock::where('kind', 'purchase')->where('reference', 'purchase:'.$purchase->id)->orderBy('item_id')->get();
        foreach ($stocks as $entry) {
            $item = Item::lockForUpdate()->findOrFail($entry->item_id);
            $this->check(! Stock::where('item_id', $item->id)->where('id', '>', $entry->id)->where('quantity_milli', '<', 0)->exists(),
                'El material/producto ya tuvo salidas. Requiere conciliación de inventario, no una anulación automática.');
            $this->check($item->quantity_milli >= $entry->quantity_milli && $item->value_cents >= $entry->value_cents, 'Existencias o valoración insuficientes para devolver la compra.');
            $item->update(['quantity_milli' => $item->quantity_milli - $entry->quantity_milli, 'value_cents' => $item->value_cents - $entry->value_cents]);
            Stock::create(['item_id' => $item->id, 'user_id' => $this->actor->id, 'kind' => 'purchase_void', 'quantity_milli' => -$entry->quantity_milli,
                'value_cents' => -$entry->value_cents, 'reference' => 'purchase:'.$purchase->id, 'reason' => $d['reason'], 'created_at' => now()]);
        }
        $entries = Ledger::where('purchase_id', $purchase->id)->whereNull('reversal_of')->lockForUpdate()->get();
        foreach ($entries as $entry) {
            if (Ledger::where('reversal_of', $entry->id)->exists()) {
                continue;
            }
            if ($entry->kind === 'fund_expense') {
                $fund = Fund::lockForUpdate()->findOrFail($entry->fund_id);
                $fund->update(['remaining_cents' => $fund->remaining_cents + abs($entry->amount_cents), 'status' => 'open']);
            }
            $this->money($entry->account_id, -$entry->amount_cents, $entry->kind.'_reversal',
                ['purchase_id' => $purchase->id, 'fund_id' => $entry->fund_id, 'method_id' => $entry->method_id, 'reversal_of' => $entry->id], $d['reason']);
        }
        $purchase->update(['review' => 'void', 'review_note' => $d['reason'], 'paid_cents' => 0, 'reimbursed_cents' => 0]);

        return $this->result('purchase', $purchase->id) + ['original_entries' => $entries->toArray(), 'reason' => $d['reason']];
    }

    private function task(array $d): array
    {
        $this->valid($d, ['description' => 'required|string|max:255', 'activity' => 'required|string|max:100',
            'quantity' => 'required|integer|min:1|max:100000', 'responsible_id' => 'required|exists:users,id',
            'purpose' => 'required|in:order,stock,internal,sample', 'line_id' => 'nullable|exists:workshop_order_lines,id',
            'item_id' => 'nullable|exists:workshop_items,id', 'printer_id' => 'nullable|exists:printers,id',
            'batch_id' => 'nullable|exists:workshop_batches,id',
            'planned_start' => 'nullable|date', 'planned_end' => 'nullable|date|after:planned_start', 'final_stage' => 'required|boolean', 'file_path' => 'nullable|string']);
        abort_unless($this->actor->isWorkshopAdmin() || (int) $d['responsible_id'] === $this->actor->id, 403);
        $this->check(User::findOrFail($d['responsible_id'])->canWorkshop('production'), 'El responsable no puede registrar producción.');
        $line = empty($d['line_id']) ? null : OrderLine::lockForUpdate()->findOrFail($d['line_id']);
        $order = $line ? $this->lockedOrder($line->dami_order_id) : null;
        $batch = empty($d['batch_id']) ? null : Batch::lockForUpdate()->findOrFail($d['batch_id']);
        $this->check(! $batch || $d['purpose'] === 'stock', 'El lote de stock no se vincula a un pedido de cliente.');
        $this->check(($d['purpose'] === 'order') === (bool) $line, 'Selecciona una línea de pedido o un destino sin pedido.');
        $this->check(! $order || ! $order->design_required || $order->design_approved, 'Falta aprobar el diseño.');
        $task = Task::create(collect($d)->only(['description', 'activity', 'quantity', 'responsible_id', 'purpose', 'printer_id',
            'planned_start', 'planned_end', 'final_stage'])->all() + ['line_id' => $line?->id, 'dami_order_id' => $order?->id,
                'item_id' => $line?->item_id ?? $batch?->item_id ?? ($d['item_id'] ?? null), 'batch_id' => $batch?->id,
                'file_path' => empty($d['file_path']) ? null : $this->paths([$d['file_path']])[0]]);
        $overlap = ! empty($d['planned_start']) && ! empty($d['planned_end']) && Task::whereKeyNot($task->id)
            ->where('status', '!=', 'finished')->where('planned_start', '<', $d['planned_end'])->where('planned_end', '>', $d['planned_start'])
            ->where(fn ($q) => $q->where('responsible_id', $task->responsible_id)->when($task->printer_id, fn ($q) => $q->orWhere('printer_id', $task->printer_id)))->exists();

        return $this->result('task', $task->id) + ['overlap_warning' => $overlap];
    }

    private function transition(array $d): array
    {
        $this->valid($d, ['event' => 'required|in:start,pause,resume,finish,problem', 'version' => 'required|integer']);
        $task = Task::lockForUpdate()->findOrFail($d['task_id']);
        abort_unless($this->actor->isWorkshopAdmin() || $task->responsible_id === $this->actor->id, 403);
        $this->check($task->version === (int) $d['version'], 'La tarea cambió en otro equipo. Recarga antes de continuar.');
        if ($d['event'] === 'problem') {
            $this->valid($d, ['incident' => 'required|string|max:2000']);
            $task->update(['incident' => $d['incident'], 'version' => $task->version + 1]);

            return $this->result('task', $task->id);
        }
        if (in_array($d['event'], ['start', 'resume'])) {
            if ($task->dami_order_id) {
                $order = $this->lockedOrder($task->dami_order_id);
                $this->check(! $order->design_required || $order->design_approved, 'Falta aprobar el diseño.');
            }
            User::whereKey($task->responsible_id)->lockForUpdate()->firstOrFail();
            $this->check(in_array($task->status, $d['event'] === 'start' ? ['pending'] : ['paused']), 'La tarea no está lista para esta acción.');
            if (! empty($d['track_manual'])) {
                $this->check(! Task::where('responsible_id', $task->responsible_id)->whereNotNull('manual_started_at')->exists(),
                    'Ya tienes un cronómetro manual activo. Paúsalo antes de iniciar otro.');
            }
            if ($task->printer_id) {
                $printer = Printer::whereKey($task->printer_id)->lockForUpdate()->firstOrFail();
                $this->check($printer->is_active && ! in_array($printer->status, ['maintenance', 'out_of_service']), 'La máquina está fuera de servicio o en mantenimiento.');
                $this->check(! Task::where('printer_id', $task->printer_id)->where('status', 'running')->exists(), 'La máquina ya tiene una tarea en curso.');
                $printer->update(['status' => 'in_use']);
            }
            $task->update(['status' => 'running', 'started_at' => now(), 'manual_started_at' => ! empty($d['track_manual']) ? now() : null, 'version' => $task->version + 1]);
        } else {
            $this->check(in_array($task->status, $d['event'] === 'pause' ? ['running'] : ['running', 'paused']), 'La tarea ya terminó o no se inició.');
            $seconds = $task->started_at ? max(0, now()->getTimestamp() - $task->started_at->getTimestamp()) : 0;
            $manual = $task->manual_started_at ? max(0, now()->getTimestamp() - $task->manual_started_at->getTimestamp()) : 0;
            $task->update(['machine_seconds' => $task->machine_seconds + ($task->printer_id ? $seconds : 0),
                'manual_seconds' => $task->manual_seconds + $manual, 'started_at' => null, 'manual_started_at' => null,
                'status' => $d['event'] === 'finish' ? 'finished' : 'paused', 'version' => $task->version + 1]);
            if ($task->printer_id) {
                Printer::whereKey($task->printer_id)->where('status', 'in_use')->update(['status' => 'available']);
            }
            if ($d['event'] === 'finish') {
                $this->production(array_replace($d, ['description' => $task->description, 'activity' => $task->activity, 'purpose' => $task->purpose,
                    'line_id' => $task->line_id, 'item_id' => $task->item_id, 'printer_id' => $task->printer_id, 'batch_id' => $task->batch_id,
                    'quantity' => $task->quantity, 'final_stage' => $task->final_stage,
                    'machine_minutes' => (int) ceil($task->machine_seconds / 60), 'manual_minutes' => (int) ceil($task->manual_seconds / 60)]), $task);
            }
        }

        return $this->result('task', $task->id);
    }

    private function production(array $d, ?Task $task = null): array
    {
        $this->valid($d, ['description' => 'required|string|max:255', 'activity' => 'required|string|max:100',
            'purpose' => 'required|in:order,stock,internal,sample', 'quantity' => 'required|integer|min:1|max:100000',
            'failed' => 'required|integer|min:0|max:100000', 'final_stage' => 'required|boolean',
            'machine_minutes' => 'required|integer|min:0|max:100000', 'manual_minutes' => 'required|integer|min:0|max:100000',
            'printer_id' => 'nullable|exists:printers,id', 'item_id' => 'nullable|exists:workshop_items,id',
            'line_id' => 'nullable|exists:workshop_order_lines,id', 'materials_confirmed' => 'required|boolean',
            'batch_id' => 'nullable|exists:workshop_batches,id',
            'consumptions' => 'present|array|max:30', 'consumptions.*.item_id' => 'required|exists:workshop_items,id',
            'consumptions.*.quantity' => 'required|numeric|min:0.001|max:10000', 'external' => 'required|numeric|min:0',
            'consumptions.*.waste' => 'nullable|numeric|min:0|max:10000',
            'incident' => 'nullable|string|max:2000', 'attachment' => 'nullable|string']);
        if (! $task && $d['manual_minutes'] > 0) {
            $this->check(! Task::where('responsible_id', $this->actor->id)->whereNotNull('manual_started_at')->exists(), 'Pausa tu cronómetro manual antes de registrar tiempo adicional.');
        }
        $line = empty($d['line_id']) ? null : OrderLine::lockForUpdate()->findOrFail($d['line_id']);
        $order = $line ? $this->lockedOrder($line->dami_order_id) : null;
        $this->check(($d['purpose'] === 'order') === (bool) $line, 'Selecciona una línea de pedido o un destino sin pedido.');
        $this->check(! $order || ! $order->design_required || $order->design_approved, 'Falta aprobar el diseño.');
        $batch = empty($d['batch_id']) ? null : Batch::lockForUpdate()->findOrFail($d['batch_id']);
        $this->check(! $batch || $d['purpose'] === 'stock', 'El lote solo corresponde a producción para stock.');
        $this->check(! $batch || $d['final_stage'] || $batch->completed === 0, 'No agregues etapas previas después de empezar a terminar el lote. Crea otro lote para reproceso.');
        $itemId = $line?->item_id ?? $batch?->item_id ?? ($d['item_id'] ?? null);
        $this->check($d['purpose'] !== 'stock' || $d['final_stage'] || $batch !== null, 'Vincula las etapas intermedias de stock a un lote.');
        if ($batch && $d['final_stage']) {
            $this->check($batch->completed + $d['quantity'] <= $batch->quantity, 'La terminación supera la cantidad pendiente del lote.');
            $this->check(! Task::where('batch_id', $batch->id)->where('final_stage', false)->where('status', '!=', 'finished')->exists(), 'Termina las etapas previas del lote antes de registrar producto terminado.');
        }
        $this->check($d['purpose'] !== 'stock' || $itemId !== null, 'La producción para stock necesita un producto de catálogo.');
        if ($d['final_stage'] && $line) {
            $this->check($line->produced + $d['quantity'] <= $line->quantity, 'La terminación supera las unidades pendientes.');
        }
        $cost = ['materials' => 0, 'external' => N::scaled($d['external']), 'rates' => [], 'complete' => (bool) $d['materials_confirmed']];
        $consumptions = [];
        foreach (collect($d['consumptions'])->sortBy('item_id') as $consumption) {
            $item = Item::lockForUpdate()->findOrFail($consumption['item_id']);
            $this->check($item->kind === 'material', 'Solo se consumen materiales en producción.');
            $qty = N::scaled($consumption['quantity'], 3);
            $this->check(N::scaled($consumption['waste'] ?? '0', 3) <= $qty, 'El desperdicio está incluido en el consumo y no puede superarlo.');
            $taken = $this->moveStock($item, -$qty, null, 'consumption', $task ? 'task:'.$task->id : 'production:'.Str::uuid());
            $cost['materials'] += $taken['cents'];
            $cost['complete'] = $cost['complete'] && $taken['complete'];
            $consumptions[] = $consumption + ['quantity_milli' => $qty, 'value_cents' => $taken['cents']];
        }
        $worker = $task?->responsible_id ?? $this->actor->id;
        foreach (['machine' => $d['machine_minutes'], 'labor' => $d['manual_minutes'], 'overhead' => $d['manual_minutes']] as $kind => $minutes) {
            $rate = $minutes ? $this->rate($kind, $d['activity'], $worker, $d['printer_id'] ?? null) : null;
            $cost[$kind] = $minutes && $rate ? N::proportional((int) $rate->hour_cents, (int) $minutes, 60) : 0;
            if ($minutes && ! $rate) {
                $cost['complete'] = false;
            }
            $cost['rates'][$kind] = $rate ? ['id' => $rate->id, 'hour_cents' => $rate->hour_cents, 'components' => $rate->parameters['component_ids'] ?? [$rate->id]] : null;
        }
        $total = $cost['materials'] + $cost['external'] + $cost['machine'] + $cost['labor'] + $cost['overhead'];
        $production = Production::create(['task_id' => $task?->id, 'dami_order_id' => $order?->id, 'line_id' => $line?->id,
            'item_id' => $itemId, 'printer_id' => $d['printer_id'] ?? null, 'user_id' => $worker,
            'description' => $d['description'], 'activity' => $d['activity'], 'purpose' => $d['purpose'], 'quantity' => $d['quantity'],
            'failed' => $d['failed'], 'final_stage' => $d['final_stage'], 'machine_minutes' => $d['machine_minutes'],
            'manual_minutes' => $d['manual_minutes'], 'consumptions' => $consumptions, 'cost_snapshot' => $cost,
            'cost_cents' => $cost['complete'] ? $total : null, 'cost_complete' => $cost['complete'], 'batch_id' => $batch?->id, 'incident' => $d['incident'] ?? null,
            'attachment' => empty($d['attachment']) ? null : $this->paths([$d['attachment']])[0], 'created_at' => now()]);
        if ($line && $d['final_stage']) {
            $line->update(['produced' => $line->produced + $d['quantity']]);
        }
        if ($d['purpose'] === 'stock' && $d['final_stage']) {
            $item = Item::lockForUpdate()->findOrFail($itemId);
            $this->check($item->kind === 'product', 'El destino de stock debe ser un producto terminado.');
            $stockCost = $total;
            $stockOverhead = $cost['overhead'];
            $stockComplete = $cost['complete'];
            if ($batch) {
                $previous = Production::where('batch_id', $batch->id)->where('final_stage', false)->get();
                $stockComplete = $stockComplete && $previous->every('cost_complete');
                $allocate = fn (int $value) => N::proportional($value, (int) $batch->completed + (int) $d['quantity'], (int) $batch->quantity) - N::proportional($value, (int) $batch->completed, (int) $batch->quantity);
                $stockCost += $allocate((int) $previous->sum('cost_cents'));
                $stockOverhead += $allocate((int) $previous->sum(fn ($p) => $p->cost_snapshot['overhead'] ?? 0));
                $batch->update(['completed' => $batch->completed + $d['quantity']]);
            }
            $this->moveStock($item, $d['quantity'] * 1000, $stockComplete ? $stockCost : null, 'production', 'production:'.$production->id, null, $stockOverhead);
        }
        if ($order) {
            $order->update(['status' => 'in_progress', 'version' => $order->version + 1]);
        }

        return $this->result('production', $production->id);
    }

    public function rate(string $kind, string $activity, int $worker, ?int $printer): ?Rate
    {
        $rates = Rate::where('kind', $kind)->whereDate('effective_on', '<=', today())
            ->when($kind === 'machine', fn ($q) => $q->where('printer_id', $printer))
            ->when($kind === 'labor', fn ($q) => $q->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $worker))
                ->where(fn ($q) => $q->whereNull('activity')->orWhere('activity', $activity)))
            ->get()->sortByDesc(fn ($r) => [($kind === 'labor' ? ($r->user_id ? ($r->activity ? 3 : 2) : ($r->activity ? 1 : 0)) : 0), $r->effective_on->format('Y-m-d'), $r->id]);
        if ($kind !== 'overhead' || $rates->isEmpty()) {
            return $rates->first();
        }
        $concepts = $rates->unique('name');
        $aggregate = clone $concepts->first();
        $aggregate->hour_cents = $concepts->sum('hour_cents');
        $aggregate->parameters = ['component_ids' => $concepts->pluck('id')->all()];

        return $aggregate;
    }

    public function estimateRecipe(Item $item, int $quantity, int $worker): array
    {
        $r = $item->recipe;
        $batch = max(1, $item->recipe_batch);
        $complete = (bool) $r;
        if (! $r) {
            return ['cost_cents' => null, 'complete' => false];
        }
        $cost = N::proportional(N::scaled($r['external']), $quantity, $batch);
        foreach ($r['materials'] as $line) {
            $m = Item::findOrFail($line['item_id']);
            if (! $m->quantity_milli || ! $m->valuation_complete) {
                $complete = false;

                continue;
            }
            $qty = N::proportional(N::scaled($line['quantity'], 3), $quantity, $batch);
            $cost += N::proportional((int) $m->value_cents, $qty, (int) $m->quantity_milli);
        }
        $machine = (int) ceil($quantity / $batch) * (int) $r['setup_minutes'] + (int) ceil($r['machine_minutes'] * $quantity / $batch);
        $manual = (int) ceil($r['manual_minutes'] * $quantity / $batch);
        $rates = [];
        foreach (['machine' => $machine, 'labor' => $manual, 'overhead' => $manual] as $kind => $minutes) {
            $rate = $minutes ? $this->rate($kind, $r['activity'], $worker, $r['printer_id'] ?? null) : null;
            if ($minutes && ! $rate) {
                $complete = false;
            }
            if ($rate) {
                $cost += N::proportional((int) $rate->hour_cents, $minutes, 60);
                $rates[$kind] = ['id' => $rate->id, 'hour_cents' => $rate->hour_cents];
            }
        }

        return ['cost_cents' => $complete ? $cost : null, 'complete' => $complete, 'rates' => $rates, 'machine_minutes' => $machine, 'manual_minutes' => $manual];
    }

    private function correctTask(array $d): array
    {
        $this->valid($d, ['reason' => 'required|string|min:5|max:1000', 'manual_minutes' => 'required|integer|min:0|max:100000', 'machine_minutes' => 'required|integer|min:0|max:100000']);
        $task = Task::lockForUpdate()->findOrFail($d['task_id']);
        $this->check($task->status === 'paused', 'Pausa primero la tarea. Una producción confirmada conserva su costo original.');
        $task->update(['manual_seconds' => $d['manual_minutes'] * 60, 'machine_seconds' => $d['machine_minutes'] * 60, 'version' => $task->version + 1]);

        return $this->result('task', $task->id);
    }

    private function batch(array $d): array
    {
        $this->valid($d, ['name' => 'required|string|max:255', 'item_id' => 'required|exists:workshop_items,id', 'quantity' => 'required|integer|min:1|max:100000']);
        $this->check(Item::findOrFail($d['item_id'])->kind === 'product', 'El lote requiere un producto terminado.');
        $b = Batch::create(['name' => $d['name'], 'item_id' => $d['item_id'], 'quantity' => $d['quantity'], 'created_by' => $this->actor->id]);

        return $this->result('batch', $b->id);
    }

    private function deliver(array $d): array
    {
        abort_unless($this->actor->canWorkshop('deliver'), 403);
        $this->valid($d, ['quantity' => 'required|integer|min:1|max:100000', 'mode' => 'required|in:immediate,pickup,shipping',
            'source' => 'required|in:stock,production', 'reference' => 'nullable|string|max:255']);
        $line = OrderLine::lockForUpdate()->findOrFail($d['line_id']);
        $order = $this->lockedOrder($line->dami_order_id);
        $this->check($line->delivered + $d['quantity'] <= $line->quantity, 'La entrega supera la cantidad pendiente.');
        $complete = true;
        $cost = null;
        $overhead = 0;
        $direct = 0;
        if ($d['source'] === 'stock') {
            $this->check($line->item_id !== null, 'Selecciona un producto de catálogo para descontar stock.');
            $taken = $this->moveStock(Item::lockForUpdate()->findOrFail($line->item_id), -$d['quantity'] * 1000, null, 'delivery', 'order:'.$order->id);
            $cost = $taken['cents'];
            $complete = $taken['complete'];
            $overhead = $taken['overhead'];
        } else {
            $previous = Delivery::where('line_id', $line->id)->get()->filter(fn ($entry) => ($entry->cost_snapshot['source'] ?? null) === 'production');
            $available = $line->produced - $previous->sum('quantity');
            $this->check($d['quantity'] <= $available, 'No hay suficientes unidades terminadas para entregar.');
            $entries = Production::where('line_id', $line->id)->get();
            $complete = $entries->isNotEmpty() && $entries->every('cost_complete')
                && $previous->every('cost_complete')
                && ! Task::where('line_id', $line->id)->where('status', '!=', 'finished')->exists();
            $remainingCost = max(0, (int) $entries->sum('cost_cents') - (int) $previous->sum(fn ($entry) => $entry->cost_snapshot['production_cost'] ?? 0));
            $remainingOverhead = max(0, (int) $entries->sum(fn ($p) => $p->cost_snapshot['overhead'] ?? 0) - (int) $previous->sum(fn ($entry) => $entry->cost_snapshot['overhead'] ?? 0));
            $cost = N::proportional($remainingCost, (int) $d['quantity'], max(1, (int) $available));
            $overhead = N::proportional($remainingOverhead, (int) $d['quantity'], max(1, (int) $available));
        }
        $productionCost = $cost;
        $directTotal = (int) Purchase::where('dami_order_id', $order->id)->where('category', 'direct')->where('review', '!=', 'void')->sum('amount_cents');
        if ($directTotal) {
            if (! $order->sale_cents) {
                $complete = false;
            } else {
                $direct = N::proportional($directTotal, (int) $line->unit_cents * ((int) $line->delivered + (int) $d['quantity']), (int) $order->sale_cents)
                    - N::proportional($directTotal, (int) $line->unit_cents * (int) $line->delivered, (int) $order->sale_cents);
                $cost += $direct;
            }
            // Unlinked manually entered external costs might describe the same expense.
            if (Production::where('dami_order_id', $order->id)->get()->contains(fn ($p) => ($p->cost_snapshot['external'] ?? 0) > 0)) {
                $complete = false;
            }
        }
        $delivery = Delivery::create(['line_id' => $line->id, 'user_id' => $this->actor->id, 'quantity' => $d['quantity'],
            'mode' => $d['mode'], 'reference' => $d['reference'] ?? null, 'cost_cents' => $complete ? $cost : null, 'cost_complete' => $complete,
            'cost_snapshot' => ['overhead' => $overhead, 'direct' => $direct, 'source' => $d['source'], 'production_cost' => $productionCost, 'direct_purchases_total' => $directTotal], 'created_at' => now()]);
        $line->update(['delivered' => $line->delivered + $d['quantity']]);
        $pending = OrderLine::where('dami_order_id', $order->id)->whereColumn('delivered', '<', 'quantity')->exists();
        $order->update(['delivery_status' => $pending ? 'partial' : 'delivered', 'status' => $pending ? $order->status : 'completed', 'version' => $order->version + 1]);

        return $this->result('delivery', $delivery->id);
    }

    private function recalculate(array $d): array
    {
        // Historical records remain immutable: publish a separate audited estimate.
        $this->valid($d, ['reason' => 'required|string|min:5|max:1000']);
        $p = Production::findOrFail($d['production_id']);
        $new = $p->cost_snapshot;
        $new['reason'] = $d['reason'];
        foreach (['machine' => $p->machine_minutes, 'labor' => $p->manual_minutes, 'overhead' => $p->manual_minutes] as $kind => $minutes) {
            $r = $this->rate($kind, $p->activity, $p->user_id, $p->printer_id);
            $new[$kind] = ! $minutes ? 0 : ($r ? N::proportional((int) $r->hour_cents, (int) $minutes, 60) : null);
            $new['rates'][$kind] = $r ? ['id' => $r->id, 'hour_cents' => $r->hour_cents, 'components' => $r->parameters['component_ids'] ?? [$r->id]] : null;
        }
        $new['complete'] = $new['complete'] && ! in_array(null,[$new['machine'], $new['labor'], $new['overhead']],true);
        $new['total_cents'] = $new['complete'] ? ($new['materials'] + $new['external'] + $new['machine'] + $new['labor'] + $new['overhead']) : null;

        return $this->result('production', $p->id) + ['recalculated_estimate' => $new, 'original_snapshot' => $p->cost_snapshot];
    }
}
