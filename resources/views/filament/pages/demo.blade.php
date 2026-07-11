<x-filament-panels::page>
    <div class="codflow-demo flex min-h-[50vh] flex-col items-center justify-center text-center">
        <x-filament::icon icon="heroicon-o-play-circle" class="mb-6 h-16 w-16 text-primary-500" />

        <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
            {{ __('codflow.demo.drive_help') }}
        </p>

        <a
            href="{{ $this->getDriveVideoUrl() }}"
            target="_blank"
            rel="noopener noreferrer"
            class="codflow-demo__blink-link inline-flex items-center gap-2 rounded-xl bg-primary-600 px-6 py-4 text-lg font-bold text-white shadow-lg transition hover:bg-primary-700"
        >
            <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="h-6 w-6" />
            {{ __('codflow.demo.watch_link') }}
        </a>
    </div>

    <style>
        .codflow-demo__blink-link {
            animation: codflow-demo-blink 1.2s ease-in-out infinite;
        }

        @keyframes codflow-demo-blink {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.55; transform: scale(1.03); }
        }
    </style>
</x-filament-panels::page>
