<x-filament-panels::page>
    <div class="codflow-demo">
        @if ($videoUrl = $this->getVideoUrl())
            <div class="codflow-demo__player">
                <video controls preload="metadata" class="w-full rounded-xl shadow-sm" style="max-height: 70vh;">
                    <source src="{{ $videoUrl }}" type="video/mp4">
                    {{ __('codflow.demo.video_unsupported') }}
                </video>
            </div>
        @else
            <div class="codflow-demo__empty rounded-xl border border-dashed border-gray-300 dark:border-gray-600 p-10 text-center">
                <x-filament::icon icon="heroicon-o-play" class="mx-auto h-12 w-12 text-gray-400" />
                <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">{{ __('codflow.demo.no_video') }}</p>
                @if ($this->canManageVideo())
                    <p class="mt-2 text-xs text-gray-500">{{ __('codflow.demo.admin_upload_hint') }}</p>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
