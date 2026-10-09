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

use context_system;
use moodle_page;
use moodle_url;
use stored_file;

/**
 * Works out whether a page gets a background video, and what to show.
 *
 * @package    local_bgbling
 * @copyright  2026 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class background {
    /** @var string Component name. */
    public const COMPONENT = 'local_bgbling';

    /** @var string File area for the uploaded video. */
    public const AREA_VIDEO = 'video';

    /** @var string File area for the poster image. */
    public const AREA_POSTER = 'poster';

    /** @var string Source setting value: use the uploaded file. */
    public const SOURCE_FILE = 'file';

    /** @var string Source setting value: use the external URL. */
    public const SOURCE_URL = 'url';

    /** @var int Screen width (px) below which the "poster only on small screens" option applies. */
    public const SMALL_SCREEN_BREAKPOINT = 768;

    /** @var string Body class added to pages that show the background. */
    public const BODY_CLASS = 'local-bgbling-active';

    /** @var string Text colour setting: white text with a dark shadow. */
    public const TEXT_LIGHT = 'light';

    /** @var string Text colour setting: dark text with a light shadow. */
    public const TEXT_DARK = 'dark';

    /** @var string Text colour setting: leave the theme's colours alone. */
    public const TEXT_THEME = 'theme';

    /**
     * Named areas the admin can tick, mapped to page type patterns.
     *
     * A trailing * matches any page type starting with the text before it.
     *
     * @var array
     */
    public const AREAS = [
        'login' => ['login-index'],
        'frontpage' => ['site-index'],
        'dashboard' => ['my-index'],
        'mycourses' => ['my-courses'],
        'course' => ['course-view-*'],
    ];

    /** @var string[] Page layouts that never get a background. */
    public const EXCLUDED_LAYOUTS = ['embedded', 'frametop', 'maintenance', 'popup', 'print', 'redirect'];

    /**
     * Whether the background should be shown on this page.
     *
     * @param moodle_page $page
     * @return bool
     */
    public static function should_show(moodle_page $page): bool {
        global $CFG;

        if (during_initial_install() || !empty($CFG->upgraderunning)) {
            return false;
        }
        if (defined('AJAX_SCRIPT') && AJAX_SCRIPT) {
            return false;
        }
        $config = get_config(self::COMPONENT);
        if (empty($config->enabled)) {
            return false;
        }
        if (in_array($page->pagelayout, self::EXCLUDED_LAYOUTS, true)) {
            return false;
        }
        $patterns = self::get_page_patterns($config->areas ?? '', $config->pagetypes ?? '');
        if (!self::page_type_matches($page->pagetype, $patterns)) {
            return false;
        }
        return self::get_source(self::get_area($page)) !== null;
    }

    /**
     * Returns the named area a page belongs to, whether or not that area is ticked.
     *
     * Pages shown only because of the additional page types setting belong to no area,
     * so they use the default settings.
     *
     * @param moodle_page $page
     * @return string|null An AREAS key, or null.
     */
    public static function get_area(moodle_page $page): ?string {
        foreach (self::AREAS as $area => $patterns) {
            if (self::page_type_matches($page->pagetype, $patterns)) {
                return $area;
            }
        }
        return null;
    }

    /**
     * Whether a location has its own video rather than using the default.
     *
     * The poster and attribution note go with the video, so they come from the same place.
     *
     * @param string|null $area An AREAS key, or null for the default settings.
     * @return bool
     */
    public static function has_own_video(?string $area): bool {
        if ($area === null || !isset(self::AREAS[$area])) {
            return false;
        }
        $source = get_config(self::COMPONENT, 'source_' . $area);
        return $source === self::SOURCE_URL || $source === self::SOURCE_FILE;
    }

    /**
     * Returns the settings name suffix for the video, poster and note to use in a location.
     *
     * @param string|null $area An AREAS key, or null for the default settings.
     * @return string Empty for the defaults, otherwise an underscore and the area key.
     */
    private static function video_suffix(?string $area): string {
        return self::has_own_video($area) ? '_' . $area : '';
    }

    /**
     * Reads a setting a location may override, falling back to the default when it is left as "Use default".
     *
     * @param string $name Setting name without a location suffix.
     * @param string|null $area An AREAS key, or null for the default settings.
     * @return string
     */
    private static function get_setting(string $name, ?string $area): string {
        if ($area !== null && isset(self::AREAS[$area])) {
            $value = get_config(self::COMPONENT, $name . '_' . $area);
            if ($value !== false && $value !== '') {
                return (string) $value;
            }
        }
        $value = get_config(self::COMPONENT, $name);
        return $value === false ? '' : (string) $value;
    }

    /**
     * Returns every file area the plugin serves: the default video and poster, and one pair per location.
     *
     * @return string[]
     */
    public static function file_areas(): array {
        $areas = [self::AREA_VIDEO, self::AREA_POSTER];
        foreach (array_keys(self::AREAS) as $area) {
            $areas[] = self::AREA_VIDEO . '_' . $area;
            $areas[] = self::AREA_POSTER . '_' . $area;
        }
        return $areas;
    }

    /**
     * Builds the list of page type patterns from the settings.
     *
     * @param string $areas Comma-separated area keys from the multi-checkbox setting.
     * @param string $pagetypes Extra page types, one per line.
     * @return string[]
     */
    public static function get_page_patterns(string $areas, string $pagetypes): array {
        $patterns = [];
        foreach (explode(',', $areas) as $area) {
            $area = trim($area);
            if (isset(self::AREAS[$area])) {
                $patterns = array_merge($patterns, self::AREAS[$area]);
            }
        }
        foreach (preg_split('/[\r\n,]+/', $pagetypes) as $line) {
            $line = trim($line);
            if ($line !== '' && preg_match('/^[a-z0-9_-]+\*?$/i', $line)) {
                $patterns[] = $line;
            }
        }
        return array_values(array_unique($patterns));
    }

    /**
     * Whether a page type matches any of the patterns.
     *
     * @param string $pagetype The page type, e.g. login-index.
     * @param string[] $patterns Exact page types, or prefixes ending in *.
     * @return bool
     */
    public static function page_type_matches(string $pagetype, array $patterns): bool {
        foreach ($patterns as $pattern) {
            if (str_ends_with($pattern, '*')) {
                if (str_starts_with($pagetype, substr($pattern, 0, -1))) {
                    return true;
                }
            } else if ($pagetype === $pattern) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the video source for a location, or null if none is usable.
     *
     * @param string|null $area An AREAS key, or null for the default settings.
     * @return video_source|null
     */
    public static function get_source(?string $area = null): ?video_source {
        $suffix = self::video_suffix($area);
        $config = get_config(self::COMPONENT);
        if (($config->{'source' . $suffix} ?? self::SOURCE_URL) === self::SOURCE_FILE) {
            $file = self::get_stored_file(self::AREA_VIDEO . $suffix);
            if ($file === null) {
                return null;
            }
            return video_source::from_file(self::file_url($file), $file->get_mimetype());
        }
        return video_source::from_url($config->{'videourl' . $suffix} ?? '');
    }

    /**
     * Returns the poster image URL for a location, or null if none is uploaded.
     *
     * @param string|null $area An AREAS key, or null for the default settings.
     * @return moodle_url|null
     */
    public static function get_poster_url(?string $area = null): ?moodle_url {
        $file = self::get_stored_file(self::AREA_POSTER . self::video_suffix($area));
        return $file === null ? null : self::file_url($file);
    }

    /**
     * Returns the attribution note for a location, cleaned to text and links only.
     *
     * @param string|null $area An AREAS key, or null for the default settings.
     * @return string Safe HTML, or an empty string when there is no note.
     */
    public static function get_caption(?string $area = null): string {
        $html = (string) get_config(self::COMPONENT, 'caption' . self::video_suffix($area));
        // Links only: the note is a small credit line, not a place for layout or images.
        $html = strip_tags($html, '<a>');
        // The editor saves an "empty" note as <p>&nbsp;</p>; a non-breaking space is not something trim() removes.
        $text = str_replace("\u{00A0}", ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (trim($text) === '') {
            return '';
        }
        $html = format_text($html, FORMAT_HTML, [
            'context' => context_system::instance(),
            'filter' => false,
            'para' => false,
        ]);
        return trim($html);
    }

    /**
     * Returns the overlay colour, falling back to black if the setting is not a plain colour value.
     *
     * @param string|null $area An AREAS key, or null for the default settings.
     * @return string
     */
    public static function get_overlay_colour(?string $area = null): string {
        $colour = trim(self::get_setting('overlaycolour', $area));
        $hex = '/^#[0-9a-f]{3,8}$/i';
        $named = '/^[a-z]{3,20}$/i';
        $functional = '/^(rgb|hsl)a?\([0-9.,%\s\/]+\)$/i';
        if (preg_match($hex, $colour) || preg_match($named, $colour) || preg_match($functional, $colour)) {
            return $colour;
        }
        return '#000000';
    }

    /**
     * Returns the overlay opacity as a fraction between 0 and 0.9.
     *
     * @param string|null $area An AREAS key, or null for the default settings.
     * @return float
     */
    public static function get_overlay_opacity(?string $area = null): float {
        $percent = (int) self::get_setting('overlayopacity', $area);
        return max(0, min(90, $percent)) / 100;
    }

    /**
     * Whether to skip the video for visitors whose browser asks for reduced motion.
     *
     * On unless the admin has turned the setting off.
     *
     * @return bool
     */
    public static function respect_reduced_motion(): bool {
        $setting = get_config(self::COMPONENT, 'respectreducedmotion');
        return $setting === false || !empty($setting);
    }

    /**
     * Returns the colour scheme for page titles that sit directly on the video.
     *
     * @param string|null $area An AREAS key, or null for the default settings.
     * @return string One of the TEXT_ constants; light when never saved.
     */
    public static function get_text_colour(?string $area = null): string {
        $setting = self::get_setting('textcolour', $area);
        if (in_array($setting, [self::TEXT_DARK, self::TEXT_THEME], true)) {
            return $setting;
        }
        return self::TEXT_LIGHT;
    }

    /**
     * Whether visitors may turn on the video's sound.
     *
     * Only uploaded files and direct links: YouTube and Vimeo players stay muted.
     *
     * @param video_source $source
     * @param string|null $area An AREAS key, or null for the default settings.
     * @return bool
     */
    public static function sound_allowed(video_source $source, ?string $area = null): bool {
        return $source->is_native() && !empty(self::get_setting('allowsound', $area));
    }

    /**
     * Returns the admin's custom CSS, made safe to place inside a style element.
     *
     * @return string
     */
    public static function get_custom_css(): string {
        $css = (string) get_config(self::COMPONENT, 'customcss');
        // CSS has no use for angle brackets; removing them means nothing can close the style element.
        return trim(str_replace(['<', '>'], '', $css));
    }

    /**
     * Returns the first file in one of the plugin's file areas.
     *
     * @param string $area One of the AREA_ constants.
     * @return stored_file|null
     */
    public static function get_stored_file(string $area): ?stored_file {
        $fs = get_file_storage();
        $files = $fs->get_area_files(context_system::instance()->id, self::COMPONENT, $area, 0, 'sortorder, id', false);
        return $files ? reset($files) : null;
    }

    /**
     * Builds the pluginfile URL for a stored file.
     *
     * The time modified goes in the item ID slot so a new upload gets a new URL, which lets the
     * file be cached for a long time.
     *
     * @param stored_file $file
     * @return moodle_url
     */
    private static function file_url(stored_file $file): moodle_url {
        return moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            self::COMPONENT,
            $file->get_filearea(),
            $file->get_timemodified(),
            $file->get_filepath(),
            $file->get_filename()
        );
    }
}
