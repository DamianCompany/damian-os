<?php

namespace Tests\Feature;

use App\Filament\Pages\Workshop;
use App\Models\DamiOrder;
use App\Models\Printer;
use App\Models\User;
use App\Models\Workshop\Account;
use App\Models\Workshop\Audit;
use App\Models\Workshop\Batch;
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
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class WorkshopTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $worker;

    private Account $account;

    private PaymentMethod $method;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'gerencia', 'is_active' => true]);
        $this->worker = User::factory()->create(['role' => 'dami_3d', 'is_active' => true]);
        $this->actingAs($this->admin);
        $this->runOp('configure', ['type' => 'account', 'name' => 'Caja de prueba', 'opening' => '1000.00']);
        $this->runOp('configure', ['type' => 'method', 'name' => 'Efectivo']);
        $this->account = Account::first();
        $this->method = PaymentMethod::first();
    }

    private function runOp(string $action, array $data, ?User $user = null, ?string $token = null): array
    {
        $user ??= $this->admin;
        $this->actingAs($user);

        return app(WorkshopService::class)->execute($user, $action, $data, $token ?? (string) Str::uuid());
    }

    private function pay(): array
    {
        return ['account_id' => $this->account->id, 'method_id' => $this->method->id];
    }

    private function sale(array $overrides = []): DamiOrder
    {
        $r = $this->runOp('sale', array_replace(['kind' => 'custom', 'customer_id' => null,
            'lines' => [['description' => 'Pieza', 'quantity' => 10, 'price' => '5.00']], 'received' => '0',
            'delivery_date' => today()->toDateString()], $overrides), $this->worker);

        return DamiOrder::findOrFail($r['id']);
    }

    private function item(string $kind = 'material', string $conversion = '1'): Item
    {
        $r = $this->runOp('configure', ['type' => 'item', 'kind' => $kind, 'name' => 'Item '.Str::random(4), 'unit' => 'g', 'purchase_unit' => 'kg', 'conversion' => $conversion]);

        return Item::findOrFail($r['id']);
    }

    private function production(array $overrides = []): array
    {
        return array_replace(['description' => 'Pieza', 'activity' => 'Armado', 'purpose' => 'internal', 'quantity' => 1, 'failed' => 0,
            'final_stage' => true, 'machine_minutes' => 0, 'manual_minutes' => 0, 'materials_confirmed' => true, 'consumptions' => [], 'external' => '0'], $overrides);
    }

    public function test_multiline_credit_sale_partial_receipts_and_retry_are_exact(): void
    {
        $order = $this->sale(['received' => '10.00'] + $this->pay());
        $this->assertSame(1000, $order->collected_cents);
        $token = (string) Str::uuid();
        $d = ['order_id' => $order->id, 'amount' => '7.35'] + $this->pay();
        $this->runOp('collect', $d, $this->worker, $token);
        $this->runOp('collect', $d, $this->worker, $token);
        $this->assertSame(1735, $order->fresh()->collected_cents);
        $this->assertSame(2, Ledger::where('kind', 'collection')->count());
        $this->assertSame('pending', $order->fresh()->delivery_status);
        $this->assertSame(101735, $this->account->fresh()->balance_cents);
    }

    public function test_quote_conversion_keeps_lines_and_never_creates_a_second_order(): void
    {
        $order = $this->sale(['kind' => 'quote']);
        $this->runOp('convert', ['order_id' => $order->id], $this->worker);
        $this->assertSame('order', $order->fresh()->document_kind);
        $this->assertSame(1, DamiOrder::count());
        $this->assertSame(1, OrderLine::count());
    }

    public function test_partial_delivery_never_marks_payment_and_stages_do_not_duplicate_units(): void
    {
        $customer = $this->runOp('customer', ['name' => 'Institución de prueba', 'type' => 'institution'], $this->worker);
        $order = $this->sale(['customer_id' => $customer['id'], 'payment_due' => today()->addDays(10)->toDateString()]);
        $line = OrderLine::where('dami_order_id', $order->id)->first();
        $this->runOp('production', $this->production(['purpose' => 'order', 'line_id' => $line->id, 'quantity' => 10, 'final_stage' => false]), $this->worker);
        $this->assertSame(0, $line->fresh()->produced);
        $this->runOp('production', $this->production(['purpose' => 'order', 'line_id' => $line->id, 'quantity' => 10]), $this->worker);
        $this->runOp('deliver', ['line_id' => $line->id, 'quantity' => 4, 'source' => 'production', 'mode' => 'shipping'], $this->worker);
        $this->assertSame(10, $line->fresh()->produced);
        $this->assertSame(4, $line->fresh()->delivered);
        $this->assertSame('partial', $order->fresh()->delivery_status);
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_stock_production_and_immediate_sale_do_not_duplicate_cash_or_inventory(): void
    {
        $item = $this->item('product');
        $this->runOp('production', $this->production(['purpose' => 'stock', 'item_id' => $item->id, 'quantity' => 10]), $this->worker);
        $this->assertSame(10000, $item->fresh()->quantity_milli);
        $this->assertSame(0, DamiOrder::count());
        $order = $this->sale(['kind' => 'stock', 'lines' => [['item_id' => $item->id, 'description' => 'Pieza', 'quantity' => 3, 'price' => '5']], 'immediate' => true, 'received' => '15'] + $this->pay());
        $this->assertSame(7000, $item->fresh()->quantity_milli);
        $this->assertSame('delivered', $order->delivery_status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(1, Delivery::count());
    }

    public function test_purchase_conversion_and_partial_material_consumption_use_weighted_average(): void
    {
        $item = $this->item('material', '1000');
        foreach (['100.00', '200.00'] as $amount) {
            $this->runOp('purchase', ['description' => 'Filamento de prueba', 'category' => 'inventory', 'source' => 'account',
                'amount' => $amount, 'purchased_on' => today()->toDateString(), 'lines' => [['item_id' => $item->id, 'quantity' => '1', 'amount' => $amount]]] + $this->pay(), $this->worker);
        }
        $r = $this->runOp('production', $this->production(['consumptions' => [['item_id' => $item->id, 'quantity' => '100']]]), $this->worker);
        $this->assertSame(1900000, $item->fresh()->quantity_milli);
        $this->assertSame(1500, Production::find($r['id'])->cost_cents);
        $this->assertSame(28500, $item->fresh()->value_cents);
    }

    public function test_fund_multiple_expenses_return_and_close_are_transfers_not_double_expenses(): void
    {
        $r = $this->runOp('assignFund', ['user_id' => $this->worker->id, 'purpose' => 'Compras', 'amount' => '100'] + $this->pay());
        foreach (['30', '20'] as $amount) {
            $p = $this->runOp('purchase', ['description' => 'Gasto de prueba', 'category' => 'overhead', 'source' => 'fund', 'fund_id' => $r['id'],
                'amount' => $amount, 'purchased_on' => today()->toDateString(), 'lines' => []], $this->worker);
            $this->runOp('reviewPurchase', ['purchase_id' => $p['id'], 'review' => 'approved']);
        }
        $this->runOp('returnFund', ['fund_id' => $r['id'], 'amount' => '50'] + $this->pay(), $this->worker);
        $this->runOp('closeFund', ['fund_id' => $r['id']]);
        $this->assertSame('closed', Fund::find($r['id'])->status);
        $this->assertSame(95000, $this->account->fresh()->balance_cents);
        $this->assertEquals(5000, Purchase::sum('amount_cents'));
    }

    public function test_employee_purchase_reimbursement_does_not_create_a_second_expense(): void
    {
        $p = $this->runOp('purchase', ['description' => 'Gasto propio', 'category' => 'overhead', 'source' => 'employee', 'amount' => '50',
            'purchased_on' => today()->toDateString(), 'lines' => []], $this->worker);
        $this->assertSame(100000, $this->account->fresh()->balance_cents);
        $this->runOp('payPurchase', ['purchase_id' => $p['id'], 'amount' => '50'] + $this->pay());
        $this->assertSame(95000, $this->account->fresh()->balance_cents);
        $this->assertSame(1, Purchase::count());
        $this->assertSame(5000, Purchase::find($p['id'])->reimbursed_cents);
    }

    public function test_cost_versions_and_labor_precedence_preserve_historical_snapshots(): void
    {
        $base = ['type' => 'rate', 'kind' => 'labor', 'name' => 'Tarifa prueba', 'mode' => 'manual', 'effective_on' => today()->toDateString()];
        $this->runOp('configure', $base + ['hour_rate' => '10']);
        $this->runOp('configure', $base + ['activity' => 'Armado', 'hour_rate' => '15']);
        $this->runOp('configure', $base + ['user_id' => $this->worker->id, 'hour_rate' => '20']);
        $this->runOp('configure', ['type' => 'rate', 'kind' => 'overhead', 'name' => 'Presupuesto prueba', 'mode' => 'breakdown', 'monthly' => '100', 'capacity' => '100', 'business_percent' => '50', 'effective_on' => today()->toDateString()]);
        $r = $this->runOp('production', $this->production(['manual_minutes' => 60]), $this->worker);
        $old = Production::find($r['id']);
        $this->assertSame(2050, $old->cost_cents);
        $this->runOp('configure', $base + ['user_id' => $this->worker->id, 'hour_rate' => '30']);
        $this->assertSame(2050, $old->fresh()->cost_cents);
        $this->assertSame(2000, $old->cost_snapshot['rates']['labor']['hour_cents']);
    }

    public function test_missing_rate_is_incomplete_instead_of_zero_profit(): void
    {
        $r = $this->runOp('production', $this->production(['manual_minutes' => 60]), $this->worker);
        $this->assertNull(Production::find($r['id'])->cost_cents);
        $this->assertFalse(Production::find($r['id'])->cost_complete);
    }

    public function test_task_finish_uses_server_times_and_records_production_once(): void
    {
        $r = $this->runOp('task', ['description' => 'Armado', 'activity' => 'Armado', 'purpose' => 'internal', 'responsible_id' => $this->worker->id,
            'quantity' => 2, 'final_stage' => true], $this->worker);
        $task = Task::find($r['id']);
        $this->runOp('transition', ['task_id' => $task->id, 'version' => $task->version, 'event' => 'start', 'track_manual' => true], $this->worker);
        $this->travel(10)->minutes();
        $token = (string) Str::uuid();
        $data = $this->production(['task_id' => $task->id, 'version' => $task->fresh()->version, 'event' => 'finish', 'manual_minutes' => 999]);
        $this->runOp('transition', $data, $this->worker, $token);
        $this->runOp('transition', $data, $this->worker, $token);
        $this->assertSame(1, Production::count());
        $this->assertSame(10, Production::first()->manual_minutes);
        $this->assertSame(2, Production::first()->quantity);
    }

    public function test_only_one_manual_timer_is_allowed_but_machine_supervision_can_be_parallel(): void
    {
        $ids = [];
        for ($i = 0; $i < 2; $i++) {
            $ids[] = $this->runOp('task', ['description' => 'Tarea', 'activity' => 'Armado', 'purpose' => 'internal', 'responsible_id' => $this->worker->id, 'quantity' => 1, 'final_stage' => false], $this->worker)['id'];
        }
        $this->runOp('transition', ['task_id' => $ids[0], 'version' => 1, 'event' => 'start', 'track_manual' => true], $this->worker);
        $this->expectException(ValidationException::class);
        $this->runOp('transition', ['task_id' => $ids[1], 'version' => 1, 'event' => 'start', 'track_manual' => true], $this->worker);
    }

    public function test_financial_reversals_preserve_original_and_cannot_be_repeated(): void
    {
        $order = $this->sale(['received' => '20'] + $this->pay());
        $entry = Ledger::where('kind', 'collection')->first();
        $this->runOp('reverse', ['ledger_id' => $entry->id, 'reason' => 'Importe ingresado por error']);
        $this->assertSame(0, $order->fresh()->collected_cents);
        $this->assertNotNull($entry->fresh());
        $this->assertSame(100000, $this->account->fresh()->balance_cents);
        $this->expectException(ValidationException::class);
        $this->runOp('reverse', ['ledger_id' => $entry->id, 'reason' => 'Intento repetido']);
    }

    public function test_worker_cannot_configure_costs_or_assign_funds_on_server(): void
    {
        $this->expectException(HttpException::class);
        $this->runOp('configure', ['type' => 'account', 'name' => 'No autorizado', 'opening' => '0'], $this->worker);
    }

    public function test_individual_restrictions_and_reports_are_enforced_on_server(): void
    {
        $this->worker->update(['workshop_restrictions' => ['purchase']]);
        $this->assertFalse($this->worker->fresh()->canWorkshop('purchase'));
        $this->assertTrue($this->worker->fresh()->canWorkshop('sale'));
        $this->actingAs($this->worker);
        Livewire::test(Workshop::class)->set('tab', 'reports')->assertDontSee('Resultado estándar');
        Livewire::test(Workshop::class)->call('export')->assertForbidden();
    }

    public function test_administrator_and_worker_pages_render_and_actions_mount(): void
    {
        foreach ([$this->admin, $this->worker] as $user) {
            $this->actingAs($user);
            $this->get(Workshop::getUrl())->assertOk();
            Livewire::test(Workshop::class)->mountAction('sale')->assertActionMounted('sale');
            Livewire::test(Workshop::class)->mountAction('production')->assertActionMounted('production');
            Livewire::test(Workshop::class)->mountAction('purchase')->assertActionMounted('purchase');
        }
    }

    public function test_batch_stages_and_partial_finishes_preserve_cost_and_units(): void
    {
        $item = $this->item('product');
        $batch = $this->runOp('batch', ['name' => 'Lote de prueba', 'item_id' => $item->id, 'quantity' => 10], $this->worker);
        $base = ['purpose' => 'stock', 'batch_id' => $batch['id'], 'item_id' => $item->id];
        $this->runOp('production', $this->production($base + ['quantity' => 10, 'final_stage' => false, 'external' => '10']), $this->worker);
        $this->assertSame(0, $item->fresh()->quantity_milli);
        $this->runOp('production', $this->production($base + ['quantity' => 3, 'external' => '6']), $this->worker);
        $this->runOp('production', $this->production($base + ['quantity' => 7, 'external' => '14']), $this->worker);
        $this->assertSame(10000, $item->fresh()->quantity_milli);
        $this->assertSame(3000, $item->fresh()->value_cents);
        $this->assertSame(10, Batch::find($batch['id'])->completed);
    }

    public function test_partial_production_and_delivery_allocate_remaining_cost_without_losing_it(): void
    {
        $order = $this->sale();
        $line = OrderLine::where('dami_order_id', $order->id)->first();
        foreach (['10', '20'] as $cost) {
            $this->runOp('production', $this->production(['purpose' => 'order', 'line_id' => $line->id, 'quantity' => 5, 'external' => $cost]), $this->worker);
            $this->runOp('deliver', ['line_id' => $line->id, 'quantity' => 5, 'source' => 'production', 'mode' => 'pickup'], $this->worker);
        }
        $this->assertEquals(3000, Delivery::sum('cost_cents'));
        $this->assertSame('delivered', $order->fresh()->delivery_status);
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_recipe_lot_preparation_is_not_multiplied_per_piece(): void
    {
        $printer = Printer::factory()->create(['name' => 'Máquina de prueba', 'location' => 'Pruebas', 'status' => 'available', 'is_active' => true]);
        $item = $this->item('product');
        $this->runOp('configure', ['type' => 'rate', 'kind' => 'machine', 'name' => 'Prueba', 'mode' => 'manual', 'printer_id' => $printer->id,
            'hour_rate' => '60', 'power_kw' => '999', 'effective_on' => today()->toDateString()]);
        $this->runOp('configure', ['type' => 'recipe', 'item_id' => $item->id, 'batch' => 10, 'materials' => [], 'printer_id' => $printer->id,
            'setup_minutes' => 5, 'machine_minutes' => 20, 'manual_minutes' => 0, 'activity' => 'Impresión 3D', 'external' => '0']);
        $estimate = app(WorkshopService::class)->estimateRecipe($item->fresh(), 10, $this->worker->id);
        $this->assertSame(25, $estimate['machine_minutes']);
        $this->assertSame(2500, $estimate['cost_cents']);
        $estimate = app(WorkshopService::class)->estimateRecipe($item->fresh(), 20, $this->worker->id);
        $this->assertSame(50, $estimate['machine_minutes']);
    }

    public function test_machine_breakdown_and_multiple_overhead_concepts_use_one_version_each(): void
    {
        $printer = Printer::factory()->create(['name' => 'Máquina de prueba', 'location' => 'Pruebas', 'status' => 'available', 'is_active' => true]);
        $r = $this->runOp('configure', ['type' => 'rate', 'kind' => 'machine', 'name' => 'Desglose', 'mode' => 'breakdown', 'printer_id' => $printer->id,
            'power_kw' => '1', 'electricity_rate' => '0.5', 'depreciable' => '1000', 'life_hours' => '1000', 'maintenance' => '0.25', 'consumables' => '0.25',
            'hour_rate' => '999', 'effective_on' => today()->toDateString()]);
        $this->assertSame(200, Rate::find($r['id'])->hour_cents);
        foreach ([['Alquiler', '100'], ['Internet', '50'], ['Alquiler', '200']] as [$name,$monthly]) {
            $this->runOp('configure', ['type' => 'rate', 'kind' => 'overhead', 'name' => $name, 'mode' => 'breakdown', 'monthly' => $monthly, 'capacity' => '100', 'effective_on' => today()->toDateString()]);
        }
        $rate = app(WorkshopService::class)->rate('overhead', 'Armado', $this->worker->id, null);
        $this->assertEquals(250, $rate->hour_cents);
        $this->assertCount(2, $rate->parameters['component_ids']);
    }

    public function test_quote_revision_keeps_old_lines_and_cannot_change_confirmed_order(): void
    {
        $order = $this->sale(['kind' => 'quote']);
        $this->runOp('reviseQuote', ['order_id' => $order->id, 'version' => $order->version, 'lines' => [['description' => 'Nueva versión', 'quantity' => 2, 'price' => '8']]], $this->worker);
        $this->assertSame(1600, $order->fresh()->sale_cents);
        $this->assertSame(1, OrderLine::count());
        $this->assertSame(2, OrderLine::withoutGlobalScopes()->count());
        $this->runOp('convert', ['order_id' => $order->id], $this->worker);
        $this->expectException(ValidationException::class);
        $this->runOp('reviseQuote', ['order_id' => $order->id, 'version' => $order->fresh()->version, 'lines' => [['description' => 'No permitido', 'quantity' => 1, 'price' => '1']]], $this->worker);
    }

    public function test_failed_stock_delivery_rolls_back_sale_and_cash_together(): void
    {
        $item = $this->item('product');
        try {
            $this->sale(['kind' => 'stock', 'immediate' => true, 'received' => '5', 'lines' => [['item_id' => $item->id, 'description' => 'Sin stock', 'quantity' => 1, 'price' => '5']]] + $this->pay());
            $this->fail('Must reject insufficient stock.');
        } catch (ValidationException) {
            $this->assertSame(0, DamiOrder::count());
            $this->assertSame(0, Ledger::where('kind', 'collection')->count());
            $this->assertSame(100000, $this->account->fresh()->balance_cents);
        }
    }

    public function test_stock_sale_period_result_does_not_subtract_inventory_purchase_twice(): void
    {
        $item = $this->item('product');
        $this->runOp('purchase', ['description' => 'Producto comprado', 'category' => 'inventory', 'source' => 'account', 'amount' => '10', 'purchased_on' => today()->toDateString(),
            'lines' => [['item_id' => $item->id, 'quantity' => '2', 'amount' => '10']]] + $this->pay(), $this->worker);
        $this->sale(['kind' => 'stock', 'immediate' => true, 'received' => '10', 'lines' => [['item_id' => $item->id, 'description' => 'Venta', 'quantity' => 1, 'price' => '10']]] + $this->pay());
        $this->actingAs($this->admin);
        $report = app(Workshop::class)->reportData();
        $this->assertEquals(5, $report['Resultado estándar de lo entregado S/']);
        $this->assertSame(1000, $item->fresh()->quantity_milli);
        $this->assertSame(500, $item->fresh()->value_cents);
    }

    public function test_worker_cannot_assign_their_own_funds(): void
    {
        $this->expectException(HttpException::class);
        $this->runOp('assignFund', ['user_id' => $this->worker->id, 'purpose' => 'No permitido', 'amount' => '10'] + $this->pay(), $this->worker);
    }

    public function test_worker_cannot_spend_another_workers_fund(): void
    {
        $other = User::factory()->create(['role' => 'dami_3d', 'is_active' => true]);
        $fund = $this->runOp('assignFund', ['user_id' => $other->id, 'purpose' => 'Otro trabajador', 'amount' => '10'] + $this->pay());
        $this->expectException(HttpException::class);
        $this->runOp('purchase', ['description' => 'No permitido', 'category' => 'overhead', 'source' => 'fund', 'fund_id' => $fund['id'],
            'amount' => '5', 'purchased_on' => today()->toDateString(), 'lines' => []], $this->worker);
    }

    public function test_confirmed_ledger_and_audit_cannot_be_edited_or_deleted(): void
    {
        $entry = Ledger::first();
        try {
            $entry->update(['amount_cents' => 1]);
            $this->fail('Immutable ledger.');
        } catch (\LogicException) {
        }
        $this->expectException(\LogicException::class);
        Audit::first()->delete();
    }

    public function test_all_operational_tabs_and_admin_configuration_actions_render(): void
    {
        $this->actingAs($this->admin);
        foreach (['orders', 'tasks', 'production', 'purchases', 'funds', 'customers', 'stock', 'cash', 'reports', 'settings', 'audit'] as $tab) {
            Livewire::test(Workshop::class)->set('tab', $tab)->assertStatus(200);
        }
        foreach (['customer', 'task', 'batch', 'fund', 'configure', 'rate', 'recipe', 'adjust', 'reverse'] as $action) {
            Livewire::test(Workshop::class)->mountAction($action)->assertActionMounted($action);
        }
    }

    public function test_worker_can_submit_quick_sale_production_and_purchase_forms(): void
    {
        $undo = Repeater::fake();
        try {
            $this->actingAs($this->worker);
            Livewire::test(Workshop::class)->callAction('sale', data: [
                'kind' => 'custom', 'lines' => [['description' => 'Venta desde formulario', 'quantity' => 2, 'price' => '12.50']],
                'received' => '5', 'account_id' => $this->account->id, 'method_id' => $this->method->id, 'delivery_date' => today()->toDateString(),
            ])->assertHasNoActionErrors();
            $this->assertSame(2500, DamiOrder::first()->sale_cents);
            Livewire::test(Workshop::class)->callAction('production', data: $this->production(['description' => 'Producción desde formulario']))->assertHasNoActionErrors();
            $this->assertSame(1, Production::count());
            Livewire::test(Workshop::class)->callAction('purchase', data: [
                'description' => 'Gasto desde formulario', 'category' => 'overhead', 'source' => 'employee', 'amount' => '2.50', 'purchased_on' => today()->toDateString(),
            ])->assertHasNoActionErrors();
            $this->assertSame(250, Purchase::first()->amount_cents);
            $this->assertSame($this->worker->id, Purchase::first()->user_id);
        } finally {
            $undo();
        }
    }

    public function test_admin_purchase_void_returns_unconsumed_stock_and_refund_without_deleting_records(): void
    {
        $item = $this->item();
        $p = $this->runOp('purchase', ['description' => 'Compra a devolver', 'category' => 'inventory', 'source' => 'account', 'amount' => '10', 'purchased_on' => today()->toDateString(),
            'lines' => [['item_id' => $item->id, 'quantity' => '2', 'amount' => '10']]] + $this->pay(), $this->worker);
        $this->runOp('voidPurchase', ['purchase_id' => $p['id'], 'reason' => 'Devolución confirmada al proveedor', 'refund_confirmed' => true]);
        $this->assertSame(0, $item->fresh()->quantity_milli);
        $this->assertSame(0, $item->fresh()->value_cents);
        $this->assertSame(100000, $this->account->fresh()->balance_cents);
        $this->assertSame('void', Purchase::find($p['id'])->review);
        $this->assertSame(1, Purchase::count());
        $this->assertSame(1, Ledger::where('kind', 'purchase_payment_reversal')->count());
    }

    public function test_admin_cannot_automatically_void_purchase_after_material_consumption(): void
    {
        $item = $this->item();
        $p = $this->runOp('purchase', ['description' => 'Compra utilizada', 'category' => 'inventory', 'source' => 'account', 'amount' => '10', 'purchased_on' => today()->toDateString(),
            'lines' => [['item_id' => $item->id, 'quantity' => '2', 'amount' => '10']]] + $this->pay(), $this->worker);
        $this->runOp('production',$this->production(['consumptions' => [['item_id' => $item->id, 'quantity' => '1']]]),$this->worker);
        $this->expectException(ValidationException::class);
        $this->runOp('voidPurchase',['purchase_id' => $p['id'], 'reason' => 'Debe conciliarse primero', 'refund_confirmed' => true]);
    }
}
