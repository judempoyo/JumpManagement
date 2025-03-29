<x-filament-panels::page>

    
    <x-filament::form wire:submit="save">
        {{ $this->form }}

        <x-filament::actions
            :actions="$this->getCachedHeaderActions()"
            :full-width="$this->hasFullWidthHeaderActions()"
        />

        <x-filament::button type="submit" class="mt-4">
            Enregistrer les modifications
        </x-filament::button>
    </x-filament::form>

</x-filament-panels::page>