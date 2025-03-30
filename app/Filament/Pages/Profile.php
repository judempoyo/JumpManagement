<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class Profile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user';
    
    protected static string $view = 'filament.pages.profile';
    
    protected static ?string $navigationLabel = 'Mon Profil';
    
    protected static ?string $slug = 'profile'; // Définit l'URL de la page
    
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(auth()->user()->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informations personnelles')
                    ->schema([
                        FileUpload::make('avatar')
                            ->label('Photo de profil')
                            ->image()
                            ->directory('avatars'),
                            
                        TextInput::make('name')
                            ->label('Nom')
                            ->required(),
                            
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                    ]),
                    
                Section::make('Changer le mot de passe')
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Mot de passe actuel')
                            ->password()
                            ->autocomplete('off'),
                            
                        TextInput::make('password')
                            ->label('Nouveau mot de passe')
                            ->password()
                            ->rules([Password::default()])
                            ->autocomplete('new-password'),
                            
                        TextInput::make('password_confirmation')
                            ->label('Confirmer le mot de passe')
                            ->password()
                            ->same('password')
                            ->autocomplete('new-password'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        $user = auth()->user();
        
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
            unset($data['password_confirmation']);
            unset($data['current_password']);
        } else {
            unset($data['password']);
        }
        
        $user->update($data);
        
        //$this->notify('success', 'Profil mis à jour avec succès');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('deleteAccount')
                ->label('Supprimer le compte')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Supprimer le compte')
                ->modalDescription('Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible.')
                ->form([
                    TextInput::make('password')
                        ->label('Mot de passe actuel')
                        ->password()
                        ->required()
                        ->rules(['current_password']),
                ])
                ->action(function () {
                    auth()->user()->delete();
                    $this->redirect('/');
                }),
        ];
    }
}