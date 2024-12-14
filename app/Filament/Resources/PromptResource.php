<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromptResource\Pages;
use App\Models\Prompt;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;

class PromptResource extends Resource
{
    protected static ?string $model = Prompt::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('identifier')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\MarkdownEditor::make('template')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\MarkdownEditor::make('system_template')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('identifier')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('test')
                    ->action(function (Prompt $record) {
                        // Hier kun je test functionaliteit toevoegen
                    })
                    ->icon('heroicon-o-play'),
            ]);
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
            'index' => Pages\ListPrompts::route('/'),
            'create' => Pages\CreatePrompt::route('/create'),
            'edit' => Pages\EditPrompt::route('/{record}/edit'),
        ];
    }


    public static function getActions(): array
    {
        return [
            Action::make('test')
                ->form([
                    Forms\Components\KeyValue::make('parameters')
                        ->label('Test Parameters'),
                    Forms\Components\TextInput::make('max_tokens')
                        ->numeric()
                        ->default(1000),
                ])
                ->action(function (Prompt $record, array $data) {
                    // Gebruik je bestaande prompt logica
                    $controller = new \App\Http\Controllers\PromptController();
                    $request = new \Illuminate\Http\Request();
                    $request->merge($data);
                    
                    return $controller->execute($request, $record->identifier);
                })
        ];
    }


}
