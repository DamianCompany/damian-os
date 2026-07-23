<?php

namespace App\Filament\Pages;

use App\Filament\Resources\DamiOrders\DamiOrderResource;
use App\Filament\Resources\Printers\PrinterResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\DamiOrder;
use App\Models\Printer;
use App\Models\User;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected string $view = 'filament.pages.dashboard';

    protected static ?string $navigationLabel = 'Inicio';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    public function getTitle(): string|Htmlable
    {
        return 'Panel de Gerencia';
    }

    protected function getViewData(): array
    {
        $orders = DamiOrder::query();
        $pendingStatuses = ['new', 'draft', 'planned'];
        $inProgressStatuses = ['in_progress', 'review', 'blocked'];
        $completedStatuses = ['ready', 'delivered'];

        $pendingOrders = (clone $orders)->whereIn('status', $pendingStatuses)->count();
        $inProgressOrders = (clone $orders)->whereIn('status', $inProgressStatuses)->count();
        $completedOrders = (clone $orders)->whereIn('status', $completedStatuses)->count();
        $totalOrders = $pendingOrders + $inProgressOrders + $completedOrders;
        $activeOrders = $pendingOrders + $inProgressOrders;
        $completionRate = $totalOrders > 0 ? (int) round(($completedOrders / $totalOrders) * 100) : 0;

        $totalPrinters = Printer::query()->where('is_active', true)->count();
        $availablePrinters = Printer::query()
            ->where('is_active', true)
            ->where('status', 'available')
            ->count();

        $overdueOrders = DamiOrder::query()
            ->whereNotIn('status', $completedStatuses)
            ->whereDate('delivery_date', '<', today())
            ->count();
        $blockedOrders = DamiOrder::query()->where('status', 'blocked')->count();
        $unavailablePrinters = Printer::query()
            ->where('is_active', true)
            ->whereIn('status', ['maintenance', 'out_of_service'])
            ->count();
        $expiringCredentials = User::query()
            ->where('is_active', true)
            ->whereNotNull('credential_expires_at')
            ->whereBetween('credential_expires_at', [now(), now()->addDays(7)])
            ->count();

        $alerts = collect([
            ['label' => 'Pedidos fuera de fecha', 'detail' => "{$overdueOrders} requieren seguimiento", 'count' => $overdueOrders, 'tone' => 'danger'],
            ['label' => 'Pedidos con bloqueo', 'detail' => "{$blockedOrders} esperan una solución", 'count' => $blockedOrders, 'tone' => 'warning'],
            ['label' => 'Impresoras no disponibles', 'detail' => "{$unavailablePrinters} requieren revisión", 'count' => $unavailablePrinters, 'tone' => 'info'],
            ['label' => 'Credenciales por vencer', 'detail' => "{$expiringCredentials} vencen en los próximos 7 días", 'count' => $expiringCredentials, 'tone' => 'info'],
        ])->filter(fn (array $alert): bool => $alert['count'] > 0);

        return [
            'activeOrders' => $activeOrders,
            'completionRate' => $completionRate,
            'totalPrinters' => $totalPrinters,
            'availablePrinters' => $availablePrinters,
            'attentionCount' => $alerts->count(),
            'progress' => [
                ['label' => 'Pendientes', 'detail' => 'Aún no iniciados', 'count' => $pendingOrders, 'tone' => 'blue'],
                ['label' => 'En proceso', 'detail' => 'Actualmente en atención', 'count' => $inProgressOrders, 'tone' => 'green'],
                ['label' => 'Completados', 'detail' => 'Terminados o entregados', 'count' => $completedOrders, 'tone' => 'teal'],
            ],
            'recentOrders' => DamiOrder::query()->latest()->limit(5)->get(),
            'printers' => Printer::query()
                ->where('is_active', true)
                ->orderByRaw("CASE status WHEN 'out_of_service' THEN 1 WHEN 'maintenance' THEN 2 WHEN 'in_use' THEN 3 ELSE 4 END")
                ->limit(4)
                ->get(),
            'alerts' => $alerts,
            'urls' => [
                'orders' => DamiOrderResource::getUrl(),
                'printers' => PrinterResource::getUrl(),
                'createCredential' => UserResource::getUrl('create'),
            ],
        ];
    }
}
