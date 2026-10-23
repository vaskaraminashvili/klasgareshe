<?php

namespace App\Enums;

enum SupportTopic: string
{
    case General = 'general';
    case Bug = 'bug';
    case Feature = 'feature';
    case Parents = 'parents';
    case Privacy = 'privacy';
    case Press = 'press';
    case Rate = 'rate';

    public function label(): string
    {
        return __('support.topics.'.$this->value);
    }
}
