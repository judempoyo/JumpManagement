<?php
namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads; // Importez WithFileUploads

#[Layout('components.layouts.auth')]
class Register extends Component
{
    use WithFileUploads; // Utilisez WithFileUploads

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public $profile_photo = null; // Pour l'upload d'image
    public $temp_profile_photo = null; // Pour l'aperçu temporaire


    protected function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class], // Valide maintenant
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()], // Valide maintenant
            'profile_photo' => 'nullable|image|max:2048',
        ];
    }

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
       /*  $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
            'profile_photo' => 'nullable|image|max:2048', // Validation pour l'image
        ]); */

        $validated = $this->validate();

        // Enregistrer l'image de profil si elle existe
        if ($this->profile_photo) {
            $validated['profile_photo'] = $this->profile_photo->store('profile-photos', 'public');
        }

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered(($user = User::create($validated))));
        $user->assignRole('cashier');
        Auth::login($user);

        $this->redirect(route('home', absolute: false), navigate: true);
    }

    /**
     * Mettre à jour l'aperçu de l'image lorsque l'utilisateur sélectionne un fichier.
     */
    public function updatedProfilePhoto()
    {
        $this->validateOnly('profile_photo'); // Valider uniquement le champ profile_photo
        $this->temp_profile_photo = $this->profile_photo->temporaryUrl(); // Afficher l'aperçu
    }

    /**
     * Supprimer l'image sélectionnée.
     */
    public function removeProfilePhoto()
    {
        $this->profile_photo = null;
        $this->temp_profile_photo = null;
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}

