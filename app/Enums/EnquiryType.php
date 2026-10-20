<?php

namespace App\Enums;

enum EnquiryType: string
{
    use Concerns;

    case Project = 'project';
    case Contact = 'contact';
    case Demo = 'demo';
    case Product = 'product';

    public function label(): string
    {
        return match ($this) {
            self::Project => 'Project enquiry',
            self::Contact => 'General contact',
            self::Demo => 'Demo request',
            self::Product => 'Product enquiry',
        };
    }
}
