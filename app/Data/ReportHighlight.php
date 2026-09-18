<?php

namespace App\Data;

final readonly class ReportHighlight
{
    public function __construct(
        public string $key,
        public string $title,
        public string $subtitle,
        public string $body,
        public string $emoji,
        public string $tile,
        public string $chip,
        public ?string $ctaHref,
        public string $ctaLabel,
    ) {}
}
