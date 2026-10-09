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
- **Tint:** a colour layer with adjustable strength (0–90%) over the video, so text stays
  readable.
- **Poster image:** a still image shown while the video loads, if the browser blocks autoplay,
  for visitors who have asked their device to reduce motion, and (optionally) on phones.
- **Accessibility:** the video is muted, decorative (hidden from screen readers), cannot be
  clicked, and does not play for visitors with "reduce motion" turned on.
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

Go to _Site administration > Plugins > Local plugins > Background video_:

1. Choose the video source and paste the address, or upload the file.
2. Upload a poster image (recommended).
3. Tick the pages to show it on.
4. Adjust the tint so the page text is readable.
5. Tick **Enable background video** and save.

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
