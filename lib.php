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

/**
 * Library functions for the background video plugin.
 *
 * @package    local_bgbling
 * @copyright  2026 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_bgbling\local\background;

/**
 * Serves the background video and poster image.
 *
 * This deliberately does NOT call require_login(): the background shows on the login page,
 * where the visitor is not logged in yet. To keep that exception narrow, only the two
 * whitelisted file areas in the system context are served (the default video and poster, and one
 * pair per location), all of which hold files an administrator uploaded for public display, and
 * only while the plugin is enabled.
 *
 * URLs carry the file's time modified as a revision, so files can be cached for a year.
 *
 * @param stdClass $course Course object (the site course here).
 * @param stdClass|null $cm Course module (unused, always null in the system context).
 * @param context $context The context of the file.
 * @param string $filearea The file area.
 * @param array $args Revision, then the file path and name.
 * @param bool $forcedownload Whether to force a download.
 * @param array $options Extra options for send_stored_file().
 * @return bool False if the file was not found; otherwise the file is sent and the script ends.
 */
function local_bgbling_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    // Unused: the files are site-wide, not tied to a course or activity.
    unset($course, $cm);

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }
    if (!in_array($filearea, background::file_areas(), true)) {
        return false;
    }
    if (!get_config('local_bgbling', 'enabled')) {
        return false;
    }

    // The first argument is the revision, which only exists to change the URL on a new upload.
    array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_bgbling', $filearea, 0, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    // Video files can be large; do not hold the session lock while streaming.
    \core\session\manager::write_close();

    $options['cacheability'] = 'public';
    $options['immutable'] = true;
    send_stored_file($file, YEARSECS, 0, $forcedownload, $options);
}

/**
 * Maps the plugin's icons to Font Awesome, for themes that use it.
 *
 * @return string[] Icon identifier => Font Awesome class.
 */
function local_bgbling_get_fontawesome_icon_map() {
    return [
        'local_bgbling:soundoff' => 'fa-volume-xmark',
        'local_bgbling:soundon' => 'fa-volume-high',
    ];
}
