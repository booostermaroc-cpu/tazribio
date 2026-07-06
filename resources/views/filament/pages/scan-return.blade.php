<x-filament-panels::page>
    <div class="codflow-scan-panel">
        <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('codflow.returns.scan_help') }}</p>

        <form wire:submit="processScan(scanCode)" class="mt-4 flex flex-col gap-4 max-w-xl">
            <x-filament::input.wrapper>
                <x-filament::input
                    type="text"
                    wire:model="scanCode"
                    placeholder="{{ __('codflow.returns.scan_placeholder') }}"
                    autofocus
                />
            </x-filament::input.wrapper>

            <x-filament::button type="submit" icon="heroicon-o-qr-code">
                {{ __('codflow.returns.scan_action') }}
            </x-filament::button>
        </form>
    </div>
</x-filament-panels::page>
