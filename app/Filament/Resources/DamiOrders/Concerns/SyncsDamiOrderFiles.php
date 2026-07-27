<?php

namespace App\Filament\Resources\DamiOrders\Concerns;

use App\Models\DamiOrder;

trait SyncsDamiOrderFiles
{
    /**
     * @var list<string>
     */
    protected array $pendingReferenceImages = [];

    protected ?string $pendingReceivedFile = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function extractFileData(array $data): array
    {
        $this->pendingReferenceImages = array_values(array_filter($data['reference_images'] ?? []));
        $this->pendingReceivedFile = filled($data['received_file'] ?? null)
            ? $data['received_file']
            : null;

        unset($data['reference_images'], $data['received_file']);

        return $data;
    }

    protected function syncOrderFiles(DamiOrder $order): void
    {
        $order->files()->delete();

        foreach ($this->pendingReferenceImages as $path) {
            $order->files()->create(['type' => 'reference', 'path' => $path]);
        }

        if ($this->pendingReceivedFile) {
            $order->files()->create(['type' => 'received', 'path' => $this->pendingReceivedFile]);
        }
    }
}
