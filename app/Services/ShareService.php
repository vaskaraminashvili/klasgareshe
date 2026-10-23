<?php

namespace App\Services;

/**
 * Outbound share text is a generic invite. It never includes a child's name,
 * XP, badges, or a URL that would publish a kid profile.
 */
class ShareService
{
    public function url(): string
    {
        return route('user-login');
    }

    public function text(): string
    {
        return (string) __('share.text');
    }

    public function title(): string
    {
        return (string) __('share.title');
    }

    public function outbound(): string
    {
        return $this->text().' '.$this->url();
    }
}
