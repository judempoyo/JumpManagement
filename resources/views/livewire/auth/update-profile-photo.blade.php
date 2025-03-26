<div>
    <form wire:submit.prevent="updateProfilePhoto">
        <!-- Aperçu de l'image actuelle ou de l'image sélectionnée -->
        <div class="mb-4">
            @if ($temp_profile_photo || $current_profile_photo)
                <div class="relative inline-block">
                    <img
                        src="{{ $temp_profile_photo ?? $current_profile_photo }}"
                        alt="Photo de profil"
                        class="w-32 h-32 rounded-full object-cover"
                    >
                    @if ($temp_profile_photo)
                        <button
                            type="button"
                            wire:click="removeProfilePhoto"
                            class="absolute top-0 right-0 bg-red-500 text-white rounded-full p-1 hover:bg-red-600"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    @endif
                </div>
            @endif
        </div>

        <!-- Champ d'upload d'image -->
        <div class="mb-4">
            <label for="profile_photo" class="block text-sm font-medium text-gray-700">Photo de profil</label>
            <input
                wire:model="profile_photo"
                type="file"
                id="profile_photo"
                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            >
            @error('profile_photo') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
        </div>

        <!-- Bouton de mise à jour -->
        <button
            type="submit"
            class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600"
        >
            Mettre à jour
        </button>
    </form>
</div>
