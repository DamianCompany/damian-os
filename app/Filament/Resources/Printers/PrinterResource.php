<?php

namespace App\Filament\Resources\Printers;

use App\Filament\Resources\Printers\Pages\CreatePrinter;
use App\Filament\Resources\Printers\Pages\EditPrinter;
use App\Filament\Resources\Printers\Pages\ListPrinters;
use App\Filament\Resources\Printers\Schemas\PrinterForm;
use App\Filament\Resources\Printers\Tables\PrintersTable;
use App\Models\Printer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PrinterResource extends Resource
{
    protected static ?string $model = Printer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPrinter;

    protected static ?string $navigationLabel = 'Impresoras';

    protected static ?string $modelLabel = 'impresora';

    protected static ?string $pluralModelLabel = 'Impresoras';

    protected static string|\UnitEnum|null $navigationGroup = 'DAMI 3D';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PrinterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PrintersTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->role === 'gerencia';
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->role === 'gerencia';
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->role === 'gerencia';
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->role === 'gerencia';
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrinters::route('/'),
            'create' => CreatePrinter::route('/create'),
            'edit' => EditPrinter::route('/{record}/edit'),
        ];
    }
}
