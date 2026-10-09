# Background video (local_bgbling)

Shows a muted, looping background video behind chosen Moodle pages: the login page, site home,
the dashboard, My courses, course pages, or any other page type you name.

The video can be:

- a file uploaded in the plugin settings (MP4 or WebM);
- a direct link to a video file (`.mp4`, `.m4v`, `.webm`, `.ogv`), using `https` if your site does;
- a YouTube video (paste the normal watch, short or share link);
- a Vimeo video (paste the normal video link, including unlisted links).

Because it is a local plugin that works through Moodle's Hooks API, it works with any theme
rather than being tied to one.

## Features

- **Pages:** tick the login page, site home, dashboard, My courses and course pages, or list
  extra page types (with `*` wildcards) for advanced use.
- **Readable titles:** the page title, breadcrumbs and page tabs sit directly on the video, so
  they are recoloured light (default) or dark, with a soft shadow, or left to the theme.
- **Sound (optional):** for uploaded files and direct links, a speaker button lets visitors turn
  the sound on. Browsers do not allow sound to start on its own, so the video starts muted; the
  choice is remembered in a cookie, and on later pages the sound returns on the first click or key
  press. YouTube and Vimeo stay muted.
- **Different video per location:** the login page, site home, dashboard, My courses and course
  pages can each have their own video, poster and attribution note, and their own tint, text
  colour and sound setting. Anything left as "Use default" comes from the general settings.
- **Attribution note:** a small credit line (text and links) in the corner of the background, for
  example "Video by Jane Doe, CC BY 4.0".
- **Tint:** a colour layer with adjustable strength (0–90%) over the video, so text stays
  readable.
- **Poster image:** a still image shown while the video loads, if the browser blocks autoplay,
  for visitors who have asked their device to reduce motion, and (optionally) on phones.
- **Accessibility:** the video is muted, decorative (hidden from screen readers) and cannot be
  clicked. By default it does not play for visitors whose device asks for reduced motion; an
  admin setting can turn this off.
- **Login page:** on Moodle 5.2 and later, where the login form sits beside a decorative side
  panel, the video fills that panel behind its welcome text. On Moodle 5.1 it fills the page.
- **Bandwidth:** nothing is downloaded until the page decides to play; uploaded files are sent
  with a one-year cache header and a new URL when replaced; there is an upload size cap; and
  phones can be set to show the poster only.
- **Custom CSS:** applied only to pages showing the background, for theme-specific tweaks.

## Things to know

- **The uploaded video and poster are public.** They have to be, because the login page is
  shown to people who are not logged in. Do not upload anything private.
- **YouTube** videos play through `youtube-nocookie.com` with controls hidden. YouTube may still
  briefly show its title or logo when the video starts and each time it loops; a site cannot
  fully prevent this.
- **Vimeo** videos use Vimeo's background player (`background=1`), which gives a clean,
  chromeless loop. As far as we know this only works for videos hosted on a paid Vimeo plan; on
  a free plan the player shows its controls.
- **Privacy:** with YouTube, Vimeo or a direct link to a video on another website, every
  visitor's browser (including on the login page, before anyone logs in) connects to that
  service, which can see their IP address. Weigh this against your privacy policy, or upload the
  video instead. The plugin itself stores no personal data.
- **Mobile:** some mobile browsers refuse to autoplay embedded videos; the poster image covers
  that case.
- **Video not playing?** Turning off animation effects in Windows (and similar settings on other
  systems) makes the browser ask websites to reduce motion, so the video is skipped. With
  debugging set to DEVELOPER, the browser console says why the video was not played.
- **Theme compatibility:** pages showing the background get the body class
  `local-bgbling-active`. The plugin makes the page background transparent for Boost and its
  child themes. Other themes may paint opaque backgrounds elsewhere; use the Custom CSS setting
  to make those transparent, for example:

  ```css
  body.local-bgbling-active #page-content {
      background: transparent;
  }
  ```

## Requirements

- Moodle 5.1, 5.2 or 5.3 LTS.
- PHP 8.2 or later (Moodle 5.2 and later need PHP 8.3 or later).

## Installing via uploaded ZIP file

1. Log in to your Moodle site as an admin and go to _Site administration > Plugins > Install
   plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add extra details
   if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually

The plugin can also be installed by putting the contents of this directory in

    {your/moodle/dirroot}/public/local/bgbling

(In Moodle 5.1 and later, the web root is the `public` directory.)

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## Setting up

Go to _Site administration > Plugins > Local plugins > Background video > General settings_:

1. Choose the video source and paste the address, or upload the file.
2. Upload a poster image (recommended).
3. Tick the pages to show it on.
4. Adjust the tint so the page text is readable.
5. Tick **Enable background video** and save.

To use a different video somewhere, open that location's page under _Background video_ (for
example _Dashboard_) and choose a video for it there.

## License

2026 Vernon Spain

This program is free software: you can redistribute it and/or modify it under the terms of
the GNU General Public License as published by the Free Software Foundation, either version 3
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See
the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If
not, see <https://www.gnu.org/licenses/>.
