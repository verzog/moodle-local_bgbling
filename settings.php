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
 * Admin settings for the background video plugin.
 *
 * @package    local_bgbling
 * @copyright  2026 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_bgbling\local\background;

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_bgbling', new lang_string('pluginname', 'local_bgbling'));
    $ADMIN->add('localplugins', $settings);

    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_configcheckbox(
            'local_bgbling/enabled',
            new lang_string('enabled', 'local_bgbling'),
            new lang_string('enabled_desc', 'local_bgbling'),
            0
        ));

        // Video source.
        $settings->add(new admin_setting_heading(
            'local_bgbling/sourceheading',
            new lang_string('sourceheading', 'local_bgbling'),
            ''
        ));

        $settings->add(new admin_setting_configselect(
            'local_bgbling/source',
            new lang_string('source', 'local_bgbling'),
            new lang_string('source_desc', 'local_bgbling'),
            background::SOURCE_URL,
            [
                background::SOURCE_URL => new lang_string('source_url', 'local_bgbling'),
                background::SOURCE_FILE => new lang_string('source_file', 'local_bgbling'),
            ]
        ));

        $settings->add(new \local_bgbling\admin\setting_videourl(
            'local_bgbling/videourl',
            new lang_string('videourl', 'local_bgbling'),
            new lang_string('videourl_desc', 'local_bgbling')
        ));
        $settings->hide_if('local_bgbling/videourl', 'local_bgbling/source', 'eq', background::SOURCE_FILE);

        $sizes = [];
        foreach ([5, 10, 20, 50, 100] as $megabytes) {
            $sizes[$megabytes] = display_size($megabytes * 1024 * 1024);
        }
        $settings->add(new admin_setting_configselect(
            'local_bgbling/maxfilesize',
            new lang_string('maxfilesize', 'local_bgbling'),
            new lang_string('maxfilesize_desc', 'local_bgbling'),
            20,
            $sizes
        ));
        $settings->hide_if('local_bgbling/maxfilesize', 'local_bgbling/source', 'eq', background::SOURCE_URL);

        $maxmegabytes = (int) get_config('local_bgbling', 'maxfilesize') ?: 20;
        $settings->add(new admin_setting_configstoredfile(
            'local_bgbling/videofile',
            new lang_string('videofile', 'local_bgbling'),
            new lang_string('videofile_desc', 'local_bgbling'),
            background::AREA_VIDEO,
            0,
            [
                'maxfiles' => 1,
                'maxbytes' => $maxmegabytes * 1024 * 1024,
                'accepted_types' => ['.mp4', '.m4v', '.webm', '.ogv'],
            ]
        ));
        $settings->hide_if('local_bgbling/videofile', 'local_bgbling/source', 'eq', background::SOURCE_URL);

        $settings->add(new admin_setting_configcheckbox(
            'local_bgbling/allowsound',
            new lang_string('allowsound', 'local_bgbling'),
            new lang_string('allowsound_desc', 'local_bgbling'),
            0
        ));

        $settings->add(new admin_setting_configstoredfile(
            'local_bgbling/poster',
            new lang_string('poster', 'local_bgbling'),
            new lang_string('poster_desc', 'local_bgbling'),
            background::AREA_POSTER,
            0,
            [
                'maxfiles' => 1,
                'accepted_types' => ['web_image'],
            ]
        ));

        if (!background::get_stored_file(background::AREA_POSTER)) {
            $settings->add(new admin_setting_description(
                'local_bgbling/noposterwarning',
                '',
                $OUTPUT->notification(get_string('noposterwarning', 'local_bgbling'), 'warning', false)
            ));
        }

        $settings->add(new admin_setting_configcheckbox(
            'local_bgbling/respectreducedmotion',
            new lang_string('respectreducedmotion', 'local_bgbling'),
            new lang_string('respectreducedmotion_desc', 'local_bgbling'),
            1
        ));

        $settings->add(new admin_setting_configcheckbox(
            'local_bgbling/posteronsmallscreens',
            new lang_string('posteronsmallscreens', 'local_bgbling'),
            new lang_string('posteronsmallscreens_desc', 'local_bgbling'),
            1
        ));

        // Where to show it.
        $settings->add(new admin_setting_heading(
            'local_bgbling/pagesheading',
            new lang_string('pagesheading', 'local_bgbling'),
            ''
        ));

        $areas = [];
        foreach (array_keys(background::AREAS) as $area) {
            $areas[$area] = new lang_string('area_' . $area, 'local_bgbling');
        }
        $settings->add(new admin_setting_configmulticheckbox(
            'local_bgbling/areas',
            new lang_string('areas', 'local_bgbling'),
            new lang_string('areas_desc', 'local_bgbling'),
            ['login' => 1],
            $areas
        ));

        $settings->add(new admin_setting_configtextarea(
            'local_bgbling/pagetypes',
            new lang_string('pagetypes', 'local_bgbling'),
            new lang_string('pagetypes_desc', 'local_bgbling'),
            ''
        ));

        // Appearance.
        $settings->add(new admin_setting_heading(
            'local_bgbling/appearanceheading',
            new lang_string('appearanceheading', 'local_bgbling'),
            ''
        ));

        $settings->add(new admin_setting_configcolourpicker(
            'local_bgbling/overlaycolour',
            new lang_string('overlaycolour', 'local_bgbling'),
            new lang_string('overlaycolour_desc', 'local_bgbling'),
            '#000000'
        ));

        $opacities = [];
        foreach (range(0, 90, 10) as $percent) {
            $opacities[$percent] = $percent . '%';
        }
        $settings->add(new admin_setting_configselect(
            'local_bgbling/overlayopacity',
            new lang_string('overlayopacity', 'local_bgbling'),
            new lang_string('overlayopacity_desc', 'local_bgbling'),
            40,
            $opacities
        ));

        $settings->add(new admin_setting_configselect(
            'local_bgbling/textcolour',
            new lang_string('textcolour', 'local_bgbling'),
            new lang_string('textcolour_desc', 'local_bgbling'),
            background::TEXT_LIGHT,
            [
                background::TEXT_LIGHT => new lang_string('textcolour_light', 'local_bgbling'),
                background::TEXT_DARK => new lang_string('textcolour_dark', 'local_bgbling'),
                background::TEXT_THEME => new lang_string('textcolour_theme', 'local_bgbling'),
            ]
        ));

        $settings->add(new admin_setting_configtextarea(
            'local_bgbling/customcss',
            new lang_string('customcss', 'local_bgbling'),
            new lang_string('customcss_desc', 'local_bgbling'),
            ''
        ));
    }
}
