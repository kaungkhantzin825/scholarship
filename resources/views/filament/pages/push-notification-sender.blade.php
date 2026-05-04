<x-filament-panels::page>
    <form wire:submit="sendNotification">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" icon="heroicon-o-paper-airplane">
                Send Notification
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
