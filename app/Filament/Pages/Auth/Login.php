<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    protected static bool $isDiscovered = false;

    public function getHeading(): string|Htmlable
    {
        return __('codflow.auth.login_title');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('codflow.auth.login_subtitle');
    }

    public function hasLogo(): bool
    {
        return true;
    }
}
