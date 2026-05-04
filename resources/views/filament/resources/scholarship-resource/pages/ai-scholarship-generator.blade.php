<x-filament-panels::page>
    <form wire:submit="generate">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" color="info" icon="heroicon-o-sparkles">
                Generate Scholarship with AI
            </x-filament::button>
            <x-filament::button tag="a" href="{{ \App\Filament\Resources\ScholarshipResource::getUrl('index') }}" color="gray" class="ml-2">
                Cancel
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
