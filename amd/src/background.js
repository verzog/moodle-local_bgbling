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

const SELECTORS = {
    ROOT: '.local-bgbling',
    MEDIA: '.local-bgbling-media[data-src]',
};

const PLAYING_CLASS = 'local-bgbling-playing';

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
 * Initialises the background video.
 *
 * @param {Object} config
 * @param {Number} config.smallscreen Width in pixels below which only the poster shows; 0 to play on all screens.
 */
export const init = ({smallscreen}) => {
    const root = document.querySelector(SELECTORS.ROOT);
    const media = root ? root.querySelector(SELECTORS.MEDIA) : null;
    if (!media) {
        return;
    }

    const queries = [window.matchMedia('(prefers-reduced-motion: reduce)')];
    if (smallscreen > 0) {
        queries.push(window.matchMedia(`(max-width: ${smallscreen - 0.02}px)`));
    }

    const update = () => {
        if (queries.some((query) => query.matches)) {
            stop(root, media);
        } else {
            start(root, media);
        }
    };

    queries.forEach((query) => query.addEventListener('change', update));
    update();
};
