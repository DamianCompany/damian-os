<?php

namespace App\Filament\Resources\ProveedoresDami3d\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

class FormularioProveedorDami3d
{
    public static function configurar(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make(self::pasos())
                ->skippable()
                ->columnSpanFull()
                ->extraAttributes(['class' => 'damian-supplier-wizard']),
        ]);
    }

    public static function pasos(): array
    {
        return [
                Step::make('Proveedor')->icon('heroicon-o-building-storefront')->schema([
                    Section::make('Identificación y contacto')->description('Registra primero cómo identificar y contactar al proveedor.')->schema([
                        Grid::make(['default'=>1,'md'=>2])->schema([
                            Select::make('tipo')->label('Tipo de proveedor')->options(['empresa'=>'Empresa','persona'=>'Persona natural','fabricante'=>'Fabricante','distribuidor'=>'Distribuidor','importador'=>'Importador','tienda_fisica'=>'Tienda física','tienda_virtual'=>'Tienda virtual','marketplace'=>'Marketplace','otro'=>'Otro'])->required()->native(false),
                            TextInput::make('razon_social')->label('Razón social / Nombre completo')->required()->maxLength(255),
                            TextInput::make('nombre_comercial')->label('Nombre comercial')->placeholder('Opcional'),
                            Select::make('tipo_documento')->label('Documento')->options(['ruc'=>'RUC','dni'=>'DNI','extranjero'=>'Documento extranjero'])->native(false),
                            TextInput::make('numero_documento')->label('Número de documento')->nullable()->unique('proveedores_dami3d','numero_documento',ignoreRecord:true)
                                ->rules(fn($get)=>match($get('tipo_documento')){'ruc'=>['nullable','regex:/^\d{11}$/'],'dni'=>['nullable','regex:/^\d{8}$/'],default=>['nullable','max:30']})
                                ->validationMessages(['regex'=>'El documento no tiene la cantidad de dígitos correcta.']),
                            TextInput::make('contacto_nombre')->label('Persona de contacto'),
                            TextInput::make('contacto_cargo')->label('Cargo'),
                            TextInput::make('whatsapp')->label('WhatsApp')->tel()->rules(['nullable','regex:/^[0-9+\s-]{9,20}$/']),
                            TextInput::make('telefono')->label('Teléfono')->tel(),
                            TextInput::make('correo_ventas')->label('Correo de ventas')->email(),
                        ]),
                    ]),
                ]),
                Step::make('Suministro')->icon('heroicon-o-cube')->schema([
                    Section::make('Qué suministra')->description('Selecciona al menos una categoría y las marcas que comercializa.')->schema([
                        Select::make('categorias')->label('Categorías')->relationship('categorias','nombre')->multiple()->preload()->searchable()->required(),
                        Select::make('marcas')->label('Marcas')->relationship('marcas','nombre')->multiple()->preload()->searchable()
                            ->createOptionForm([TextInput::make('nombre')->label('Nueva marca')->required()->unique('marcas_proveedor_dami3d','nombre')]),
                        Grid::make(['default'=>1,'md'=>3])->schema([
                            TextInput::make('entrega_promedio_dias')->label('Entrega promedio')->suffix('días')->numeric()->minValue(0),
                            TextInput::make('compra_minima')->label('Compra mínima')->prefix('S/')->numeric()->minValue(0),
                            TextInput::make('costo_envio')->label('Costo de envío')->prefix('S/')->numeric()->minValue(0),
                        ]),
                    ]),
                ]),
                Step::make('Condiciones')->icon('heroicon-o-banknotes')->schema([
                    Section::make('Condiciones comerciales')->schema([
                        Grid::make(['default'=>1,'md'=>3])->schema([
                            Select::make('moneda')->label('Moneda')->options(['PEN'=>'Soles','USD'=>'Dólares'])->default('PEN')->native(false),
                            Select::make('forma_pago')->label('Forma de pago')->options(['efectivo'=>'Efectivo','transferencia'=>'Transferencia','yape'=>'Yape','plin'=>'Plin','tarjeta'=>'Tarjeta','plataforma'=>'Plataforma','contraentrega'=>'Contraentrega','otro'=>'Otro'])->native(false),
                            Select::make('condicion_pago')->label('Condición')->options(['contado'=>'Contado','adelanto_total'=>'Adelanto completo','adelanto_parcial'=>'Adelanto parcial','contraentrega'=>'Contraentrega','credito'=>'Crédito'])->native(false),
                            Toggle::make('emite_factura')->label('Emite factura'), Toggle::make('emite_boleta')->label('Emite boleta'), Toggle::make('ofrece_garantia')->label('Ofrece garantía'),
                        ]),
                        Textarea::make('condiciones_garantia')->label('Condiciones de garantía')->rows(2)->visible(fn($get)=>$get('ofrece_garantia'))->columnSpanFull(),
                    ]),
                    Section::make('Ubicación')->collapsed()->schema([
                        Grid::make(['default'=>1,'md'=>3])->schema([
                            TextInput::make('pais')->label('País')->default('Perú'), TextInput::make('departamento')->label('Departamento'), TextInput::make('provincia')->label('Provincia'), TextInput::make('distrito')->label('Distrito'), TextInput::make('direccion')->label('Dirección')->columnSpan(2),
                        ]),
                    ]),
                    Section::make('Control de Gerencia')->visible(fn():bool=>auth()->user()?->role==='gerencia')->schema([
                        Grid::make(['default'=>1,'md'=>2])->schema([
                            Select::make('estado')->label('Estado')->options(['evaluacion'=>'En evaluación','activo'=>'Activo','suspendido'=>'Suspendido','bloqueado'=>'Bloqueado','inactivo'=>'Inactivo'])->default('evaluacion')->native(false),
                            Toggle::make('principal')->label('Proveedor principal'),
                            Textarea::make('motivo_estado')->label('Motivo del estado')->rows(2)->columnSpanFull(),
                        ]),
                    ]),
                    Section::make('Datos bancarios restringidos')->collapsed()->visible(fn():bool=>auth()->user()?->role==='gerencia')->schema([
                        Grid::make(['default'=>1,'md'=>3])->schema([
                            TextInput::make('banco')->label('Banco'), TextInput::make('tipo_cuenta')->label('Tipo de cuenta'), TextInput::make('numero_cuenta')->label('Número de cuenta'), TextInput::make('cci')->label('CCI'), TextInput::make('titular_cuenta')->label('Titular'), TextInput::make('yape')->label('Yape'), TextInput::make('plin')->label('Plin'),
                        ]),
                    ]),
                ]),
        ];
    }
}
