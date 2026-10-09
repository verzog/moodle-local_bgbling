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

namespace local_bgbling\admin;

use admin_setting_configtext;
use local_bgbling\local\video_source;

/**
 * Text setting that only accepts YouTube, Vimeo or direct video file URLs.
 *
 * @package    local_bgbling
 * @copyright  2026 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setting_videourl extends admin_setting_configtext {
    /**
     * Constructor.
     *
     * @param string $name Setting name.
     * @param string $visiblename Localised name.
     * @param string $description Localised description.
     */
    public function __construct($name, $visiblename, $description) {
        parent::__construct($name, $visiblename, $description, '', PARAM_RAW_TRIMMED, 60);
    }

    /**
     * Checks the URL is empty or one we know how to play.
     *
     * @param string $data The submitted value.
     * @return bool|string True if valid, otherwise an error message.
     */
    public function validate($data) {
        $data = trim((string) $data);
        if ($data === '' || video_source::from_url($data) !== null) {
            return true;
        }
        return get_string('invalidvideourl', 'local_bgbling');
    }
}
