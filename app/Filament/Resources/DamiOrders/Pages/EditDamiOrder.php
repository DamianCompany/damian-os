<?php

namespace App\Filament\Resources\DamiOrders\Pages;

use App\Filament\Resources\DamiOrders\Concerns\SyncsDamiOrderFiles;
use App\Filament\Resources\DamiOrders\DamiOrderResource;
use App\Filament\Resources\DamiOrders\Schemas\DamiOrderForm;
use App\Models\DamiOrder;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\Concerns\HasWizard;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Component;

class EditDamiOrder extends EditRecord
{
    use HasWizard {
        getWizardComponent as getBaseWizardComponent;
    }
    use SyncsDamiOrderFiles;

    protected static string $resource = DamiOrderResource::class;

    public function getTitle(): string
    {
        return "Editar {$this->record->order_number}";
    }

    public function getSubheading(): ?string
    {
        return 'Completa una etapa a la vez.';
    }

    public function getSteps(): array
    {
        return DamiOrderForm::steps();
    }

    public function getWizardComponent(): Component
    {
        return $this->getBaseWizardComponent()
            ->persistStepInQueryString('etapa')
            ->extraAttributes(['class' => 'damian-order-wizard']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var DamiOrder $order */
        $order = $this->record;
        $data['reference_images'] = $order->referenceFiles()->orderBy('id')->pluck('path')->all();
        $data['received_file'] = $order->files()->where('type', 'received')->value('path');

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->extractFileData($data);
    }

    protected function afterSave(): void
    {
        /** @var DamiOrder $order */
        $order = $this->record;
        $this->syncOrderFiles($order);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('Eliminar pedido'),
        ];
    }
}
