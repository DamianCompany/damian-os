<?php

namespace App\Filament\Resources\ProveedoresServicioTecnico\Pages;

use App\Filament\Resources\ProveedoresServicioTecnico\ProveedorServicioTecnicoResource;
use App\Filament\Resources\ProveedoresServicioTecnico\Schemas\FormularioProveedorServicioTecnico;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\HasWizard;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Component;

class EditarProveedorServicioTecnico extends EditRecord
{
    use HasWizard {
        getWizardComponent as getBaseWizardComponent;
    }

    protected static string $resource = ProveedorServicioTecnicoResource::class;
    protected static ?string $title = 'Editar proveedor técnico';

    public function getSteps(): array
    {
        return FormularioProveedorServicioTecnico::pasos();
    }

    public function getWizardComponent(): Component
    {
        return $this->getBaseWizardComponent()
            ->persistStepInQueryString('etapa')
            ->extraAttributes(['class' => 'damian-supplier-wizard damian-technical-supplier-wizard']);
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->label('Guardar cambios');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('volver')
                ->label('Volver a la ficha')
                ->icon('heroicon-o-arrow-left')
                ->url(static::getResource()::getUrl('view', ['record' => $this->record])),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }
}
