<?php

namespace App\Enums;

use App\Enums\Concerns\HasColorAndLabel;

enum OrderSource: string
{
    use HasColorAndLabel;

    case Facebook = 'facebook';
    case Instagram = 'instagram';
    case Tiktok = 'tiktok';
    case Website = 'website';
    case Whatsapp = 'whatsapp';
    case Other = 'other';


    public function color(): string
    {
        return 'gray';
    }
}
