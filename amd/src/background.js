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
 * Starts the background video, unless the visitor prefers reduced motion or is on a small screen.
 *
 * The media URL sits in data-src so nothing downloads until this module decides the video
 * should play. When it should not, the poster image (if any) stays visible instead.
 *
 * @module     local_bgbling/background
 * @copyright  2026 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Log from 'core/log';

const SELECTORS = {
    ROOT: '.local-bgbling',
    MEDIA: '.local-bgbling-media[data-src]',
    LOGIN_PANEL: '.login-layout-left',
    FRAME: '.local-bgbling-frame',
};

/** Aspect ratio of YouTube and Vimeo players. */
const FRAME_RATIO = 16 / 9;

const PLAYING_CLASS = 'local-bgbling-playing';
const CONTAINED_CLASS = 'local-bgbling-contained';
const HOST_CLASS = 'local-bgbling-host';
const ACTIVE_BODY_CLASS = 'local-bgbling-active';

/**
 * Loads and plays the media element.
 *
 * @param {HTMLElement} root The background container.
 * @param {HTMLElement} media The video or iframe element.
 */
const start = (root, media) => {
    if (media.tagName === 'VIDEO') {
        if (!media.getAttribute('src')) {
            media.disablePictureInPicture = true;
            media.setAttribute('src', media.dataset.src);
        }
        // Muted autoplay can still be refused (e.g. data saver); the poster then stays visible.
        media.play().then(() => root.classList.add(PLAYING_CLASS)).catch(() => root.classList.remove(PLAYING_CLASS));
        return;
    }
    if (!media.getAttribute('src')) {
        // Keep the poster in view until the embedded player has loaded.
        media.addEventListener('load', () => root.classList.add(PLAYING_CLASS), {once: true});
        media.setAttribute('src', media.dataset.src);
    }
};

/**
 * Stops the media element and shows the poster again.
 *
 * @param {HTMLElement} root The background container.
 * @param {HTMLElement} media The video or iframe element.
 */
const stop = (root, media) => {
    root.classList.remove(PLAYING_CLASS);
    if (media.tagName === 'VIDEO') {
        media.pause();
        return;
    }
    // Removing the iframe source stops the embedded player and its network traffic.
    media.removeAttribute('src');
};

/**
 * Keeps a YouTube or Vimeo frame sized to cover the layer, cropping the overflow like object-fit: cover.
 *
 * @param {HTMLElement} root The background container.
 */
const coverWithFrame = (root) => {
    const frame = root.querySelector(SELECTORS.FRAME);
    if (!frame) {
        return;
    }
    const resize = () => {
        const width = root.clientWidth;
        const height = root.clientHeight;
        const wide = width / height > FRAME_RATIO;
        frame.style.width = `${wide ? width : height * FRAME_RATIO}px`;
        frame.style.height = `${wide ? width / FRAME_RATIO : height}px`;
    };
    new ResizeObserver(resize).observe(root);
    resize();
};

/**
 * Moves the background into the login page's side panel, where the theme has one.
 *
 * Moodle 5.2 and later show the login form on the right and a decorative panel on the left;
 * the video belongs in that panel rather than behind the whole page.
 *
 * @param {HTMLElement} root The background container.
 * @returns {HTMLElement|null} The panel the background now sits in, or null if it stays full-page.
 */
const placeInLoginPanel = (root) => {
    const panel = document.querySelector(SELECTORS.LOGIN_PANEL);
    if (!panel) {
        return null;
    }
    panel.classList.add(HOST_CLASS);
    panel.prepend(root);
    root.classList.add(CONTAINED_CLASS);
    // The full-page transparency rules are not wanted when the video only fills the panel.
    document.body.classList.remove(ACTIVE_BODY_CLASS);
    return panel;
};

/**
 * Initialises the background video.
 *
 * @param {Object} config
 * @param {Number} config.smallscreen Width in pixels below which only the poster shows; 0 to play on all screens.
 * @param {Boolean} config.respectmotion Whether to skip the video when the visitor prefers reduced motion.
 */
export const init = ({smallscreen, respectmotion}) => {
    const root = document.querySelector(SELECTORS.ROOT);
    const media = root ? root.querySelector(SELECTORS.MEDIA) : null;
    if (!media) {
        return;
    }
    // Move before loading anything: moving an iframe in the page reloads it.
    const panel = placeInLoginPanel(root);
    coverWithFrame(root);

    // Each condition that stops the video, with the reason logged for admins diagnosing a missing video.
    const conditions = [];
    if (respectmotion) {
        const query = window.matchMedia('(prefers-reduced-motion: reduce)');
        conditions.push({
            applies: () => query.matches,
            reason: 'the browser or operating system asks for reduced motion ' +
                '(for example, Windows animation effects are turned off)',
        });
    }
    if (smallscreen > 0) {
        const query = window.matchMedia(`(max-width: ${smallscreen - 0.02}px)`);
        conditions.push({
            applies: () => query.matches,
            reason: `the window is narrower than ${smallscreen} pixels and "Poster only on small screens" is on`,
        });
    }
    if (panel) {
        conditions.push({
            applies: () => panel.offsetWidth === 0,
            reason: 'the login page side panel is hidden at this window size',
        });
    }

    let lastreason;
    const update = () => {
        const blocking = conditions.find((condition) => condition.applies());
        const reason = blocking ? blocking.reason : '';
        if (reason === lastreason) {
            return;
        }
        lastreason = reason;
        if (blocking) {
            Log.info(`local_bgbling: background video not played because ${reason}.`);
            stop(root, media);
        } else {
            start(root, media);
        }
    };

    // Window size and system settings can change while the page is open.
    window.addEventListener('resize', update);
    window.matchMedia('(prefers-reduced-motion: reduce)').addEventListener('change', update);
    update();
};
