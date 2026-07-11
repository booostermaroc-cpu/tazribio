<?php

namespace App\Filament\Pages;

use App\Filament\Support\Nav;
use App\Support\RolePermission;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class DemoPage extends Page
{
    public const DRIVE_VIDEO_URL = 'https://drive.google.com/file/d/13qkuv8WMWwcee0-7GxleQf2JhIDfESVi/view?usp=sharing';

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

    public function getDriveVideoUrl(): string
    {
        return self::DRIVE_VIDEO_URL;
    }
}
