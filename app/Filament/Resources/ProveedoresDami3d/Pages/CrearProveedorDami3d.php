<?php
namespace App\Filament\Resources\ProveedoresDami3d\Pages;
use App\Filament\Resources\ProveedoresDami3d\ProveedorDami3dResource;
use App\Filament\Resources\ProveedoresDami3d\Schemas\FormularioProveedorDami3d;
use Filament\Resources\Pages\Concerns\HasWizard;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Component;

class CrearProveedorDami3d extends CreateRecord
{
    use HasWizard {
        getWizardComponent as getBaseWizardComponent;
    }

    protected static string $resource = ProveedorDami3dResource::class;
    protected static ?string $title = 'Registrar proveedor';

    public function getSteps(): array
    {
        return FormularioProveedorDami3d::pasos();
    }

    public function getWizardComponent(): Component
    {
        return $this->getBaseWizardComponent()
            ->persistStepInQueryString('etapa')
            ->extraAttributes(['class' => 'damian-supplier-wizard']);
    }

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()->label('Crear proveedor');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }
}
