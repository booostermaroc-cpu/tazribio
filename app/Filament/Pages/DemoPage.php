<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Filament\Support\Nav;
use App\Services\SettingService;
use App\Support\RolePermission;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

class DemoPage extends Page
{
    /** Taille max. vidéo : 2 Go (Filament attend des kilo-octets). */
    private const MAX_VIDEO_SIZE_KB = 2 * 1024 * 1024;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlay;

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.demo';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && RolePermission::canAccessResource($user, 'demo');
    }

    public static function getNavigationLabel(): string
    {
        return Nav::label('demo');
    }

    public function getVideoUrl(): ?string
    {
        return SettingService::demoVideoUrl();
    }

    public function canManageVideo(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    protected function getHeaderActions(): array
    {
        if (! $this->canManageVideo()) {
            return [];
        }

        return [
            Action::make('uploadVideo')
                ->label(__('codflow.demo.upload_action'))
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->form([
                    FileUpload::make('demo_video')
                        ->label(__('codflow.demo.upload_label'))
                        ->helperText(__('codflow.demo.upload_help'))
                        ->disk('public')
                        ->directory('demos')
                        ->visibility('public')
                        ->acceptedFileTypes([
                            'video/mp4',
                            'video/webm',
                            'video/quicktime',
                            'video/x-msvideo',
                        ])
                        ->maxSize(self::MAX_VIDEO_SIZE_KB)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $setting = SettingService::get();
                    $newPath = is_array($data['demo_video'] ?? null)
                        ? ($data['demo_video'][0] ?? null)
                        : ($data['demo_video'] ?? null);

                    if (blank($newPath)) {
                        Notification::make()
                            ->title(__('codflow.notifications.error'))
                            ->body(__('codflow.demo.upload_failed'))
                            ->danger()
                            ->send();

                        return;
                    }

                    if (filled($setting->demo_video) && $setting->demo_video !== $newPath) {
                        Storage::disk('public')->delete($setting->demo_video);
                    }

                    $setting->update(['demo_video' => $newPath]);

                    Notification::make()
                        ->title(__('codflow.notifications.success'))
                        ->body(__('codflow.demo.upload_success'))
                        ->success()
                        ->send();
                }),
            Action::make('deleteVideo')
                ->label(__('codflow.demo.delete_action'))
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('codflow.demo.delete_confirm'))
                ->visible(fn (): bool => filled(SettingService::get()->demo_video))
                ->action(function (): void {
                    $setting = SettingService::get();

                    if (filled($setting->demo_video)) {
                        Storage::disk('public')->delete($setting->demo_video);
                        $setting->update(['demo_video' => null]);
                    }

                    Notification::make()
                        ->title(__('codflow.notifications.success'))
                        ->body(__('codflow.demo.delete_success'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
