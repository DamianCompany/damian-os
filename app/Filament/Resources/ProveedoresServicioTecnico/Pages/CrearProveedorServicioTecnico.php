<?php

namespace App\Filament\Resources\ProveedoresServicioTecnico\Pages;

use App\Filament\Resources\ProveedoresServicioTecnico\ProveedorServicioTecnicoResource;
use App\Filament\Resources\ProveedoresServicioTecnico\Schemas\FormularioProveedorServicioTecnico;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\HasWizard;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Component;

class CrearProveedorServicioTecnico extends CreateRecord
{
    use HasWizard {
        getWizardComponent as getBaseWizardComponent;
    }

    protected static string $resource = ProveedorServicioTecnicoResource::class;
    protected static ?string $title = 'Registrar proveedor técnico';

    public function getSubheading(): ?string
    {
        return 'Completa una sección a la vez.';
    }

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

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Crear proveedor');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }
}
