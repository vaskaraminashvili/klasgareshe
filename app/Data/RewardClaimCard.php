<?php

namespace App\Data;

final readonly class RewardClaimCard
{
    public function __construct(
        public string $type,
        public string $reference,
        public string $emoji,
        public string $title,
        public string $subtitle,
        public string $action,
        public string $buttonClass,
    ) {}

    /**
     * @return array{type: string, reference: string, emoji: string, title: string, subtitle: string, action: string, buttonClass: string}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'reference' => $this->reference,
            'emoji' => $this->emoji,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'action' => $this->action,
            'buttonClass' => $this->buttonClass,
        ];
    }
}
