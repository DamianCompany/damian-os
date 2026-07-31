<?php
namespace App\Filament\Resources\ProveedoresDami3d\Pages;
use App\Filament\Resources\ProveedoresDami3d\ProveedorDami3dResource;
use App\Filament\Resources\ProveedoresDami3d\Schemas\FormularioProveedorDami3d;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\HasWizard;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Component;

class EditarProveedorDami3d extends EditRecord
{
    use HasWizard {
        getWizardComponent as getBaseWizardComponent;
    }

    protected static string $resource = ProveedorDami3dResource::class;
    protected static ?string $title = 'Editar proveedor';

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
