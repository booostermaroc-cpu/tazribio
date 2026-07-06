<?php

namespace App\Services;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrCodeService
{
    public function generateDataUri(string $content): string
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'scale' => 4,
            'imageTransparent' => false,
        ]);

        $png = (new QRCode($options))->render($content);

        if (str_starts_with($png, 'data:image')) {
            return $png;
        }

        return 'data:image/png;base64,'.base64_encode($png);
    }
}
