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

import Config from 'core/config';
import Log from 'core/log';

const SELECTORS = {
    ROOT: '.local-bgbling',
    MEDIA: '.local-bgbling-media[data-src]',
    LOGIN_PANEL: '.login-layout-left',
    FRAME: '.local-bgbling-frame',
    SOUND_BUTTON: '.local-bgbling-sound',
    CONTROLS: '.local-bgbling-controls',
};

/** Cookie holding the visitor's sound choice. */
const SOUND_COOKIE = 'local_bgbling_sound';

/** How long the sound choice is remembered: one year, in seconds. */
const SOUND_COOKIE_LIFETIME = 365 * 24 * 60 * 60;

/** Aspect ratio of YouTube and Vimeo players. */
const FRAME_RATIO = 16 / 9;

const PLAYING_CLASS = 'local-bgbling-playing';
const CONTAINED_CLASS = 'local-bgbling-contained';
const HOST_CLASS = 'local-bgbling-host';

/**
 * Reads the visitor's remembered sound choice.
 *
 * @returns {Boolean} Whether the visitor last turned the sound on.
 */
const soundPreferred = () => {
    // Browsers list the cookie with the most specific path first, so a Moodle site at a nested path
    // reads its own choice rather than one from a site higher up on the same domain.
    const cookie = document.cookie.split('; ').find((entry) => entry.startsWith(`${SOUND_COOKIE}=`));
    return cookie === `${SOUND_COOKIE}=on`;
};

/**
 * Remembers the visitor's sound choice.
 *
 * A cookie rather than local storage, because Moodle clears local storage at each login and
 * whenever its JavaScript caches are purged.
 *
 * @param {Boolean} on Whether the sound is on.
 */
const rememberSound = (on) => {
    const path = new URL(Config.wwwroot).pathname.replace(/\/?$/, '/');
    const secure = window.location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = `${SOUND_COOKIE}=${on ? 'on' : 'off'}; Max-Age=${SOUND_COOKIE_LIFETIME}; Path=${path}; SameSite=Lax${secure}`;
};

/**
 * Sets up the button that turns the video's sound on and off.
 *
 * Browsers refuse to start a video with sound before the visitor has interacted with the page, so the
 * video always starts muted. If the visitor turned the sound on before, it comes back on their first
 * click, tap or key press anywhere on the page.
 *
 * @param {HTMLElement} media The video element.
 * @returns {Object} Callbacks for when the video starts and stops playing.
 */
const setupSound = (media) => {
    const button = document.querySelector(SELECTORS.SOUND_BUTTON);
    if (!button || media.tagName !== 'VIDEO') {
        return {playing: () => null, stopped: () => null};
    }

    const setSound = (on) => {
        media.muted = !on;
        button.setAttribute('aria-pressed', on ? 'true' : 'false');
    };
    button.addEventListener('click', () => {
        const on = media.muted;
        setSound(on);
        rememberSound(on);
    });

    const unmuteOnFirstGesture = (e) => {
        if (button.contains(e.target)) {
            // The button's own click handler decides.
            return;
        }
        document.removeEventListener('click', unmuteOnFirstGesture, true);
        document.removeEventListener('keydown', unmuteOnFirstGesture, true);
        if (soundPreferred() && !button.hidden) {
            setSound(true);
        }
    };

    return {
        playing: () => {
            button.hidden = false;
            if (!soundPreferred()) {
                return;
            }
            if (navigator.userActivation && navigator.userActivation.hasBeenActive) {
                setSound(true);
            } else {
                // Click, not pointerdown: a touch only counts as user interaction once the finger lifts,
                // and unmuting before that makes the browser pause the video instead.
                document.addEventListener('click', unmuteOnFirstGesture, true);
                document.addEventListener('keydown', unmuteOnFirstGesture, true);
            }
        },
        stopped: () => {
            button.hidden = true;
            setSound(false);
        },
    };
};

/**
 * Loads and plays the media element.
 *
 * @param {HTMLElement} root The background container.
 * @param {HTMLElement} media The video or iframe element.
 * @param {Object} sound Sound button callbacks from setupSound.
 */
const start = (root, media, sound) => {
    if (media.tagName === 'VIDEO') {
        if (!media.getAttribute('src')) {
            media.disablePictureInPicture = true;
            media.setAttribute('src', media.dataset.src);
        }
        // Muted autoplay can still be refused (e.g. data saver); the poster then stays visible.
        media.play().then(() => {
            root.classList.add(PLAYING_CLASS);
            sound.playing();
            return true;
        }).catch(() => root.classList.remove(PLAYING_CLASS));
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
 * @param {Object} sound Sound button callbacks from setupSound.
 */
const stop = (root, media, sound) => {
    root.classList.remove(PLAYING_CLASS);
    sound.stopped();
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
    // The sound button and attribution note belong with the video they control and credit.
    const controls = document.querySelector(SELECTORS.CONTROLS);
    if (controls) {
        panel.append(controls);
    }
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
    const sound = setupSound(media);

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
            stop(root, media, sound);
        } else {
            start(root, media, sound);
        }
    };

    // Window size and system settings can change while the page is open.
    window.addEventListener('resize', update);
    window.matchMedia('(prefers-reduced-motion: reduce)').addEventListener('change', update);
    update();
};
