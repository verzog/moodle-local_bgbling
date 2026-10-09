<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_bgbling\local;

/**
 * Tests for deciding where the background shows.
 *
 * @package    local_bgbling
 * @copyright  2026 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(background::class)]
final class background_test extends \advanced_testcase {
    /**
     * Page type matching cases.
     *
     * @return array
     */
    public static function page_type_provider(): array {
        return [
            'Login ticked' => ['login', '', 'login-index', true],
            'Login not ticked' => ['dashboard', '', 'login-index', false],
            'Dashboard' => ['login,dashboard', '', 'my-index', true],
            'Course wildcard' => ['course', '', 'course-view-topics', true],
            'Course wildcard, other page' => ['course', '', 'course-edit', false],
            'Extra exact type' => ['', "user-profile\n", 'user-profile', true],
            'Extra wildcard' => ['', "mod-forum-*\r\n", 'mod-forum-discuss', true],
            'Extra invalid line ignored' => ['', "<b>\n", '<b>', false],
            'Unknown area ignored' => ['nonsense', '', 'nonsense', false],
            'Nothing configured' => ['', '', 'login-index', false],
        ];
    }

    /**
     * Area and page type settings match the right pages.
     *
     * @param string $areas Areas setting.
     * @param string $pagetypes Additional page types setting.
     * @param string $pagetype Page type being shown.
     * @param bool $expected Whether it should match.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('page_type_provider')]
    public function test_page_type_matches(string $areas, string $pagetypes, string $pagetype, bool $expected): void {
        $patterns = background::get_page_patterns($areas, $pagetypes);
        $this->assertSame($expected, background::page_type_matches($pagetype, $patterns));
    }

    /**
     * Builds a page with the given type and layout.
     *
     * @param string $pagetype
     * @param string $layout
     * @return \moodle_page
     */
    private function make_page(string $pagetype, string $layout = 'login'): \moodle_page {
        $page = new \moodle_page();
        $page->set_context(\context_system::instance());
        $page->set_pagetype($pagetype);
        $page->set_pagelayout($layout);
        return $page;
    }

    /**
     * The background only shows when enabled, on a ticked area, with a usable source.
     */
    public function test_should_show(): void {
        $this->resetAfterTest();
        $page = $this->make_page('login-index');

        set_config('areas', 'login', 'local_bgbling');
        set_config('source', background::SOURCE_URL, 'local_bgbling');
        set_config('videourl', 'https://vimeo.com/76979871', 'local_bgbling');
        $this->assertFalse(background::should_show($page), 'Disabled by default.');

        set_config('enabled', 1, 'local_bgbling');
        $this->assertTrue(background::should_show($page));

        $this->assertFalse(background::should_show($this->make_page('my-index', 'mydashboard')));
        $this->assertFalse(background::should_show($this->make_page('login-index', 'popup')));

        set_config('videourl', 'https://example.com/page.html', 'local_bgbling');
        $this->assertFalse(background::should_show($page), 'No usable source.');

        set_config('source', background::SOURCE_FILE, 'local_bgbling');
        $this->assertFalse(background::should_show($page), 'No file uploaded.');

        $this->store_file(background::AREA_VIDEO, 'loop.mp4');
        $this->assertTrue(background::should_show($page));
        $source = background::get_source();
        $this->assertSame(video_source::TYPE_FILE, $source->type);
        $this->assertStringContainsString('/local_bgbling/video/', $source->url->out(false));
        $this->assertStringEndsWith('/loop.mp4', $source->url->out(false));
    }

    /**
     * The poster URL comes from the poster file area.
     */
    public function test_get_poster_url(): void {
        $this->resetAfterTest();
        $this->assertNull(background::get_poster_url());
        $this->store_file(background::AREA_POSTER, 'poster.jpg');
        $this->assertStringEndsWith('/poster.jpg', background::get_poster_url()->out(false));
    }

    /**
     * Overlay and custom CSS settings are sanitised.
     */
    public function test_sanitised_settings(): void {
        $this->resetAfterTest();

        set_config('overlaycolour', '#1a2b3c', 'local_bgbling');
        $this->assertSame('#1a2b3c', background::get_overlay_colour());
        set_config('overlaycolour', 'rgba(0, 0, 0, 0.5)', 'local_bgbling');
        $this->assertSame('rgba(0, 0, 0, 0.5)', background::get_overlay_colour());
        set_config('overlaycolour', 'red; background-image: url(x)', 'local_bgbling');
        $this->assertSame('#000000', background::get_overlay_colour());

        set_config('overlayopacity', 40, 'local_bgbling');
        $this->assertEqualsWithDelta(0.4, background::get_overlay_opacity(), 0.001);
        set_config('overlayopacity', 500, 'local_bgbling');
        $this->assertEqualsWithDelta(0.9, background::get_overlay_opacity(), 0.001);

        set_config('customcss', '#page { color: red; }</style><script>alert(1)</script>', 'local_bgbling');
        $css = background::get_custom_css();
        $this->assertStringNotContainsString('<', $css);
        $this->assertStringNotContainsString('>', $css);
    }

    /**
     * The reduce-motion preference is respected unless the admin turns it off.
     */
    public function test_respect_reduced_motion(): void {
        $this->resetAfterTest();
        unset_config('respectreducedmotion', 'local_bgbling');
        $this->assertTrue(background::respect_reduced_motion(), 'On when never saved.');
        set_config('respectreducedmotion', 0, 'local_bgbling');
        $this->assertFalse(background::respect_reduced_motion());
        set_config('respectreducedmotion', 1, 'local_bgbling');
        $this->assertTrue(background::respect_reduced_motion());
    }

    /**
     * The text colour defaults to light and only accepts known values.
     */
    public function test_get_text_colour(): void {
        $this->resetAfterTest();
        $this->assertSame(background::TEXT_LIGHT, background::get_text_colour(), 'Light when never saved.');
        set_config('textcolour', background::TEXT_DARK, 'local_bgbling');
        $this->assertSame(background::TEXT_DARK, background::get_text_colour());
        set_config('textcolour', background::TEXT_THEME, 'local_bgbling');
        $this->assertSame(background::TEXT_THEME, background::get_text_colour());
        set_config('textcolour', 'purple', 'local_bgbling');
        $this->assertSame(background::TEXT_LIGHT, background::get_text_colour());
    }

    /**
     * Sound is only offered for native video, and only when the admin allows it.
     */
    public function test_sound_allowed(): void {
        $this->resetAfterTest();
        $direct = video_source::from_url('https://cdn.example.com/loop.mp4');
        $youtube = video_source::from_url('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

        $this->assertFalse(background::sound_allowed($direct), 'Off by default.');
        set_config('allowsound', 1, 'local_bgbling');
        $this->assertTrue(background::sound_allowed($direct));
        $this->assertFalse(background::sound_allowed($youtube), 'YouTube stays muted.');
    }

    /**
     * Pages belong to the first named area whose page types match; custom page types belong to none.
     */
    public function test_get_area(): void {
        $this->assertSame('login', background::get_area($this->make_page('login-index')));
        $this->assertSame('dashboard', background::get_area($this->make_page('my-index', 'mydashboard')));
        $this->assertSame('course', background::get_area($this->make_page('course-view-topics', 'course')));
        $this->assertNull(background::get_area($this->make_page('user-profile', 'standard')));
    }

    /**
     * A location uses the default video, poster and note unless it has its own video,
     * and then uses its own for all three.
     */
    public function test_location_video_poster_and_caption(): void {
        $this->resetAfterTest();
        set_config('source', background::SOURCE_URL, 'local_bgbling');
        set_config('videourl', 'https://vimeo.com/76979871', 'local_bgbling');
        set_config('caption', '<p>Default credit</p>', 'local_bgbling');
        $this->store_file(background::AREA_POSTER, 'default.jpg');
        // Location settings are ignored while the location still uses the default video.
        set_config('videourl_login', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'local_bgbling');
        set_config('caption_login', 'Login credit', 'local_bgbling');

        $this->assertFalse(background::has_own_video('login'));
        $this->assertSame(video_source::TYPE_VIMEO, background::get_source('login')->type);
        $this->assertStringEndsWith('/default.jpg', background::get_poster_url('login')->out(false));
        $this->assertSame('Default credit', background::get_caption('login'));

        set_config('source_login', background::SOURCE_URL, 'local_bgbling');
        $this->assertTrue(background::has_own_video('login'));
        $this->assertSame(video_source::TYPE_YOUTUBE, background::get_source('login')->type);
        $this->assertNull(background::get_poster_url('login'), 'Own video, no own poster: none.');
        $this->assertSame('Login credit', background::get_caption('login'));
        $this->store_file(background::AREA_POSTER . '_login', 'login.jpg');
        $this->assertStringEndsWith('/login.jpg', background::get_poster_url('login')->out(false));

        // Other locations and custom page types keep the default.
        $this->assertSame(video_source::TYPE_VIMEO, background::get_source('dashboard')->type);
        $this->assertSame(video_source::TYPE_VIMEO, background::get_source(null)->type);

        // An uploaded file for the location comes from its own file area.
        set_config('source_login', background::SOURCE_FILE, 'local_bgbling');
        $this->assertNull(background::get_source('login'), 'No file uploaded for the location yet.');
        $this->store_file(background::AREA_VIDEO . '_login', 'login.webm');
        $this->assertStringContainsString('/local_bgbling/video_login/', background::get_source('login')->url->out(false));
    }

    /**
     * Tint, text colour and sound fall back to the default when a location leaves them as "Use default".
     */
    public function test_location_overrides(): void {
        $this->resetAfterTest();
        set_config('overlaycolour', '#112233', 'local_bgbling');
        set_config('overlayopacity', 40, 'local_bgbling');
        set_config('textcolour', background::TEXT_LIGHT, 'local_bgbling');
        set_config('allowsound', 1, 'local_bgbling');
        foreach (['overlaycolour', 'overlayopacity', 'textcolour', 'allowsound'] as $name) {
            set_config($name . '_dashboard', '', 'local_bgbling');
        }
        $direct = video_source::from_url('https://cdn.example.com/loop.mp4');

        $this->assertSame('#112233', background::get_overlay_colour('dashboard'));
        $this->assertEqualsWithDelta(0.4, background::get_overlay_opacity('dashboard'), 0.001);
        $this->assertSame(background::TEXT_LIGHT, background::get_text_colour('dashboard'));
        $this->assertTrue(background::sound_allowed($direct, 'dashboard'));

        set_config('overlaycolour_dashboard', '#ffffff', 'local_bgbling');
        set_config('overlayopacity_dashboard', 0, 'local_bgbling');
        set_config('textcolour_dashboard', background::TEXT_DARK, 'local_bgbling');
        set_config('allowsound_dashboard', 0, 'local_bgbling');
        $this->assertSame('#ffffff', background::get_overlay_colour('dashboard'));
        $this->assertEqualsWithDelta(0.0, background::get_overlay_opacity('dashboard'), 0.001, 'Zero is a real choice.');
        $this->assertSame(background::TEXT_DARK, background::get_text_colour('dashboard'));
        $this->assertFalse(background::sound_allowed($direct, 'dashboard'));

        // The defaults themselves are unchanged.
        $this->assertSame('#112233', background::get_overlay_colour('login'));
        $this->assertTrue(background::sound_allowed($direct, null));
    }

    /**
     * The attribution note keeps text and links, and nothing else.
     */
    public function test_get_caption_cleaning(): void {
        $this->resetAfterTest();
        $this->assertSame('', background::get_caption());
        set_config('caption', '<p>&nbsp;</p>', 'local_bgbling');
        $this->assertSame('', background::get_caption(), 'An empty editor paragraph is no note.');

        set_config('caption', '<p><strong>Video</strong> by <a href="https://example.com/jane">Jane</a>'
            . '<img src="x.png" onerror="alert(1)"><script>alert(2)</script>'
            . ' <a href="javascript:alert(3)">bad</a></p>', 'local_bgbling');
        $caption = background::get_caption();
        $this->assertStringContainsString('<a href="https://example.com/jane">Jane</a>', $caption);
        $this->assertStringContainsString('Video by', $caption);
        $this->assertStringNotContainsString('<img', $caption);
        $this->assertStringNotContainsString('<script', $caption);
        $this->assertStringNotContainsString('<strong', $caption);
        $this->assertStringNotContainsString('javascript:', $caption);
    }

    /**
     * The plugin serves the default video and poster areas and one pair per location.
     */
    public function test_file_areas(): void {
        $areas = background::file_areas();
        $this->assertContains('video', $areas);
        $this->assertContains('poster_login', $areas);
        $this->assertContains('video_course', $areas);
        $this->assertCount(2 + 2 * count(background::AREAS), $areas);
    }

    /**
     * Stores a dummy file in one of the plugin's file areas.
     *
     * @param string $area
     * @param string $filename
     */
    private function store_file(string $area, string $filename): void {
        get_file_storage()->create_file_from_string([
            'contextid' => \context_system::instance()->id,
            'component' => 'local_bgbling',
            'filearea' => $area,
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename,
        ], 'dummy');
    }
}
