<?php

namespace App\Services;

/**
 * Only locales with both UI strings and question content are listed.
 * `lang/en` is the framework fallback and is not a product language yet.
 */
class AppLocaleService
{
    /**
     * @return list<array{code: string, native: string, flag: string, keywords: string}>
     */
    public function available(): array
    {
        return [
            [
                'code' => 'ka',
                'native' => (string) __('language.native'),
                'flag' => '🇬🇪',
                'keywords' => 'ქართული ქართული ენა georgian kartuli sakartvelo',
            ],
        ];
    }
}
