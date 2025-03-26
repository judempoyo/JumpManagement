<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
class UpdateProfilePhoto extends Component
{
    use WithFileUploads;

    public $profile_photo;
    public $temp_profile_photo; // Pour stocker temporairement l'image uploadée
    public $current_profile_photo; // Pour afficher l'image actuelle

    protected $rules = [
        'profile_photo' => 'nullable|image|max:2048', // 2MB max
    ];

    public function mount()
    {
        // Charger l'image de profil actuelle
        $this->current_profile_photo = Auth::user()->profile_photo
            ? asset('storage/' . Auth::user()->profile_photo)
            : asset('images/default-profile.png');
    }

    public function updatedProfilePhoto()
    {
        // Valider et afficher un aperçu de l'image sélectionnée
        $this->validateOnly('profile_photo');
        $this->temp_profile_photo = $this->profile_photo->temporaryUrl();
    }

    public function removeProfilePhoto()
    {
        // Supprimer l'image sélectionnée
        $this->profile_photo = null;
        $this->temp_profile_photo = null;
    }

    public function updateProfilePhoto()
    {
       
    
        $this->validate();
    
        if ($this->profile_photo) {
    
            if (Auth::user()->profile_photo) {
                Storage::disk('public')->delete(Auth::user()->profile_photo);
            }
    
            try {
                $profilePhotoPath = $this->profile_photo->store('profile-photos', 'public');
            } catch (\Exception $e) {
            
                session()->flash('error', 'Une erreur est survenue lors de l\'enregistrement de la photo.');
                return;
            }
    
            Auth::user()->update(['profile_photo' => $profilePhotoPath]);
    
            $this->current_profile_photo = asset('storage/' . $profilePhotoPath);
            $this->profile_photo = null;
            $this->temp_profile_photo = null;
    
            session()->flash('message', 'Photo de profil mise à jour avec succès !');
        } else {
            session()->flash('error', 'Aucune photo de profil n\'a été téléchargée.');
        }
    }
    public function render()
    {
        return view('livewire.auth.update-profile-photo');
    }
}