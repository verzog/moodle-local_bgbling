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
        return self::get_source() !== null;
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
     * Returns the configured video source, or null if none is usable.
     *
     * @return video_source|null
     */
    public static function get_source(): ?video_source {
        $config = get_config(self::COMPONENT);
        if (($config->source ?? self::SOURCE_URL) === self::SOURCE_FILE) {
            $file = self::get_stored_file(self::AREA_VIDEO);
            if ($file === null) {
                return null;
            }
            return video_source::from_file(self::file_url($file), $file->get_mimetype());
        }
        return video_source::from_url($config->videourl ?? '');
    }

    /**
     * Returns the poster image URL, or null if none is uploaded.
     *
     * @return moodle_url|null
     */
    public static function get_poster_url(): ?moodle_url {
        $file = self::get_stored_file(self::AREA_POSTER);
        return $file === null ? null : self::file_url($file);
    }

    /**
     * Returns the overlay colour, falling back to black if the setting is not a plain colour value.
     *
     * @return string
     */
    public static function get_overlay_colour(): string {
        $colour = trim((string) get_config(self::COMPONENT, 'overlaycolour'));
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
     * @return float
     */
    public static function get_overlay_opacity(): float {
        $percent = (int) get_config(self::COMPONENT, 'overlayopacity');
        return max(0, min(90, $percent)) / 100;
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
