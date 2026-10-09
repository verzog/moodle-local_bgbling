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

namespace local_bgbling;

use core\hook\output\before_http_headers;
use core\hook\output\before_standard_head_html_generation;
use core\hook\output\before_standard_top_of_body_html_generation;
use html_writer;
use local_bgbling\local\background;

/**
 * Output hook callbacks that add the background video to nominated pages.
 *
 * @package    local_bgbling
 * @copyright  2026 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class hook_callbacks {
    /**
     * Adds a body class to targeted pages, so the plugin's CSS can make page backgrounds transparent.
     *
     * This runs before the page layout is rendered, which is the last point a body class can be added.
     *
     * @param before_http_headers $hook
     */
    public static function before_http_headers(before_http_headers $hook): void {
        $page = $hook->renderer->get_page();
        if (background::should_show($page)) {
            $page->add_body_class(background::BODY_CLASS);
        }
    }

    /**
     * Adds the admin's custom CSS to the head of targeted pages only.
     *
     * @param before_standard_head_html_generation $hook
     */
    public static function before_standard_head_html_generation(before_standard_head_html_generation $hook): void {
        if (!background::should_show($hook->renderer->get_page())) {
            return;
        }
        $css = background::get_custom_css();
        if ($css !== '') {
            $hook->add_html(html_writer::tag('style', $css));
        }
    }

    /**
     * Outputs the background layer at the top of the body and loads the AMD module that starts it.
     *
     * @param before_standard_top_of_body_html_generation $hook
     */
    public static function before_standard_top_of_body_html_generation(
        before_standard_top_of_body_html_generation $hook
    ): void {
        $page = $hook->renderer->get_page();
        if (!background::should_show($page)) {
            return;
        }
        $source = background::get_source();
        $poster = background::get_poster_url();
        $opacity = background::get_overlay_opacity();

        $context = [
            'isnative' => $source->is_native(),
            'src' => $source->url->out(false),
            'posterurl' => $poster ? $poster->out(false) : '',
            'hasoverlay' => $opacity > 0,
            'overlaycolour' => background::get_overlay_colour(),
            'overlayopacity' => sprintf('%.2F', $opacity),
            'respectmotion' => background::respect_reduced_motion(),
        ];
        $hook->add_html($hook->renderer->render_from_template('local_bgbling/background', $context));

        $smallscreen = get_config('local_bgbling', 'posteronsmallscreens') ? background::SMALL_SCREEN_BREAKPOINT : 0;
        $page->requires->js_call_amd('local_bgbling/background', 'init', [[
            'smallscreen' => $smallscreen,
            'respectmotion' => background::respect_reduced_motion(),
        ]]);
    }
}
