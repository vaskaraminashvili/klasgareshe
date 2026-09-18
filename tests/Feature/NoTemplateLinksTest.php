<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The `kidzio/` template links screens as flat `.html` files. Every one of those that
 * survives a port is a 404 in the running app, so no shipped view or page script may
 * contain one. See docs/tasks/T02-dead-links-sweep.md.
 */
class NoTemplateLinksTest extends TestCase
{
    /**
     * `href="x.html"`, `href: 'x.html'` and `location.href = 'x.html'`.
     * Matches links only, so the "Page script for home.html" banners stay legal.
     */
    private const LINK_PATTERN = '/href\s*[:=]\s*[\'"][^\'"]*\.html/i';

    /**
     * Prebuilt vendor bundle (Swiper + the template's own nav highlighting). Its `.html`
     * map is inert here and docs/rails-swiper.md says not to touch this file.
     */
    private const VENDOR_SCRIPTS = ['index.js'];

    public function test_no_blade_view_links_to_a_template_html_file(): void
    {
        $offenders = [];

        foreach ($this->bladeFiles() as $file) {
            if (preg_match(self::LINK_PATTERN, (string) file_get_contents($file), $found) === 1) {
                $offenders[] = $this->relative($file).' → '.$found[0];
            }
        }

        $this->assertSame([], $offenders, "Dead template links found:\n".implode("\n", $offenders));
    }

    public function test_no_page_script_loaded_by_a_view_links_to_a_template_html_file(): void
    {
        $offenders = [];

        foreach ($this->loadedScripts() as $script) {
            if (preg_match(self::LINK_PATTERN, (string) file_get_contents($script), $found) === 1) {
                $offenders[] = $this->relative($script).' → '.$found[0];
            }
        }

        $this->assertSame([], $offenders, "Dead template links found:\n".implode("\n", $offenders));
    }

    /**
     * @return list<string>
     */
    private function bladeFiles(): array
    {
        $files = glob(resource_path('views').'/{,*/,*/*/}*.blade.php', GLOB_BRACE);

        $this->assertNotEmpty($files, 'No Blade views found to scan.');

        return array_values($files ?: []);
    }

    /**
     * Only the scripts a Blade view actually asks for — the rest of `public/assets/js`
     * is unported template leftovers.
     *
     * @return list<string>
     */
    private function loadedScripts(): array
    {
        $scripts = [];

        foreach ($this->bladeFiles() as $file) {
            preg_match_all(
                '/asset\(\s*[\'"]assets\/js\/([^\'"]+\.js)[\'"]\s*\)/',
                (string) file_get_contents($file),
                $matches,
            );

            foreach ($matches[1] as $name) {
                if (in_array($name, self::VENDOR_SCRIPTS, true)) {
                    continue;
                }

                $path = public_path('assets/js/'.$name);

                if (is_file($path)) {
                    $scripts[$name] = $path;
                }
            }
        }

        $this->assertNotEmpty($scripts, 'No page scripts found to scan.');

        return array_values($scripts);
    }

    private function relative(string $path): string
    {
        return str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
    }
}
