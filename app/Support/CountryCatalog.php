<?php

namespace App\Support;

/**
 * Ranking regions. Learner counts are never stored here — only the picker catalog.
 */
final class CountryCatalog
{
    /**
     * @return list<array{code: string, emoji: string, continent: string, keywords: string}>
     */
    public static function all(): array
    {
        return [
            ['code' => 'ge', 'emoji' => '🇬🇪', 'continent' => 'europe', 'keywords' => 'georgia sakartvelo საქართველო ge'],
            ['code' => 'us', 'emoji' => '🇺🇸', 'continent' => 'americas', 'keywords' => 'united states usa america american us'],
            ['code' => 'gb', 'emoji' => '🇬🇧', 'continent' => 'europe', 'keywords' => 'united kingdom uk britain england british gb'],
            ['code' => 'ca', 'emoji' => '🇨🇦', 'continent' => 'americas', 'keywords' => 'canada canadian ca'],
            ['code' => 'au', 'emoji' => '🇦🇺', 'continent' => 'oceania', 'keywords' => 'australia australian aussie au oz'],
            ['code' => 'fr', 'emoji' => '🇫🇷', 'continent' => 'europe', 'keywords' => 'france french fr'],
            ['code' => 'de', 'emoji' => '🇩🇪', 'continent' => 'europe', 'keywords' => 'germany german deutschland de'],
            ['code' => 'br', 'emoji' => '🇧🇷', 'continent' => 'americas', 'keywords' => 'brazil brasil brazilian br'],
            ['code' => 'in', 'emoji' => '🇮🇳', 'continent' => 'asia', 'keywords' => 'india indian bharat in'],
            ['code' => 'es', 'emoji' => '🇪🇸', 'continent' => 'europe', 'keywords' => 'spain spanish espana es'],
            ['code' => 'jp', 'emoji' => '🇯🇵', 'continent' => 'asia', 'keywords' => 'japan japanese nippon jp'],
            ['code' => 'mx', 'emoji' => '🇲🇽', 'continent' => 'americas', 'keywords' => 'mexico mexican mx'],
            ['code' => 'it', 'emoji' => '🇮🇹', 'continent' => 'europe', 'keywords' => 'italy italian italia it'],
            ['code' => 'nl', 'emoji' => '🇳🇱', 'continent' => 'europe', 'keywords' => 'netherlands dutch holland nl'],
            ['code' => 'kr', 'emoji' => '🇰🇷', 'continent' => 'asia', 'keywords' => 'south korea korean kr'],
            ['code' => 'cn', 'emoji' => '🇨🇳', 'continent' => 'asia', 'keywords' => 'china chinese zhongguo cn'],
            ['code' => 'ng', 'emoji' => '🇳🇬', 'continent' => 'africa', 'keywords' => 'nigeria nigerian ng'],
            ['code' => 'za', 'emoji' => '🇿🇦', 'continent' => 'africa', 'keywords' => 'south africa za'],
            ['code' => 'nz', 'emoji' => '🇳🇿', 'continent' => 'oceania', 'keywords' => 'new zealand kiwi nz'],
        ];
    }

    /**
     * @return array{code: string, emoji: string, continent: string, keywords: string}|null
     */
    public static function find(?string $code): ?array
    {
        if ($code === null || $code === '') {
            return null;
        }

        $code = strtolower($code);

        foreach (self::all() as $country) {
            if ($country['code'] === $code) {
                return $country;
            }
        }

        return null;
    }

    public static function emoji(?string $code): string
    {
        return self::find($code)['emoji'] ?? '';
    }

    public static function keywords(?string $code): string
    {
        $found = self::find($code);

        return $found === null ? '' : $found['code'].' '.$found['keywords'];
    }

    public static function name(?string $code): string
    {
        $found = self::find($code);

        if ($found === null) {
            return (string) __('country.worldwide');
        }

        return (string) __('country.names.'.$found['code']);
    }
}
