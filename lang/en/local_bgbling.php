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
 * English strings for the background video plugin.
 *
 * @package    local_bgbling
 * @copyright  2026 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['allowsound'] = 'Allow sound';
$string['allowsound_desc'] = 'Show a speaker button so visitors can turn on the video\'s sound. Browsers do not allow sound to start on its own, so the video always starts muted; the visitor\'s choice is remembered in their browser, and on later pages the sound comes back on their first click or key press. Works with uploaded files and direct video links, not YouTube or Vimeo.';
$string['appearanceheading'] = 'Appearance';
$string['area_course'] = 'Course pages';
$string['area_dashboard'] = 'Dashboard';
$string['area_frontpage'] = 'Site home (front page)';
$string['area_login'] = 'Login page';
$string['area_mycourses'] = 'My courses';
$string['areas'] = 'Show on';
$string['areas_desc'] = 'The pages that show the background video.';
$string['caption'] = 'Attribution note';
$string['caption_desc'] = 'Optional small note shown in the corner of the background, for example to credit the video or picture and its licence. Text and links only; other formatting is removed.';
$string['customcss'] = 'Custom CSS';
$string['customcss_desc'] = 'Extra CSS added only to the pages that show the background. Use it to make your theme\'s page areas transparent, or to add a background to text that is hard to read over the video. Pages showing the background have the body class <code>local-bgbling-active</code>. Angle brackets are removed.';
$string['enabled'] = 'Enable background video';
$string['enabled_desc'] = 'Show the background video on the pages selected below.';
$string['generalsettings'] = 'General settings';
$string['iframetitle'] = 'Decorative background video';
$string['invalidvideourl'] = 'Enter a YouTube or Vimeo video address, or a direct link to an .mp4, .m4v, .webm or .ogv file. On a site that uses https, direct links must also use https.';
$string['locationcaption_desc'] = 'Attribution note for this location\'s own video or picture. Text and links only.';
$string['locationoverlaycolour_desc'] = 'Tint colour for this location. Leave empty to use the default.';
$string['locationposter_desc'] = 'Poster image for this location\'s own video. Leave empty for no poster.';
$string['locationsettings_desc'] = 'Settings for {$a} only. Anything left as "Use default" (or empty) comes from the general settings. The background only shows here if this location is ticked under "Show on" in the general settings.';
$string['locationsource_desc'] = 'Use the default video, or a different one for this location. The poster image and attribution note go with the video: with the default video, the default poster and note are used too.';
$string['maxfilesize'] = 'Maximum video file size';
$string['maxfilesize_desc'] = 'The largest video file that can be uploaded. The video downloads on every page that shows it (browsers cache it after the first visit), so keep it short and well compressed. Save the settings after changing this before uploading.';
$string['noposterwarning'] = 'No poster image is uploaded. Visitors who do not get the video (because they have asked for reduced motion, are on a small screen, or their browser blocks autoplay) will see a plain dark background instead.';
$string['overlaycolour'] = 'Tint colour';
$string['overlaycolour_desc'] = 'A colour layer drawn over the video, so text on the page stays readable.';
$string['overlayopacity'] = 'Tint strength';
$string['overlayopacity_desc'] = 'How strongly the tint colour covers the video. 0% turns the tint off.';
$string['ownvideonotice'] = 'These locations use their own video, set on their own settings pages, so changing the video here does not affect them: {$a}.';
$string['pagesheading'] = 'Where to show it';
$string['pagetypes'] = 'Additional page types';
$string['pagetypes_desc'] = 'For advanced use: extra Moodle page types to show the background on, one per line, for example <code>user-profile</code>. End a line with * to match every page type that starts with it, for example <code>mod-forum-*</code>. The page type is the page\'s body ID without the leading <code>page-</code>.';
$string['pluginname'] = 'Background video';
$string['poster'] = 'Poster image';
$string['poster_desc'] = 'Optional still image shown while the video loads, if the browser blocks autoplay, for visitors who have asked their device to reduce motion, and on small screens when that option is on. Strongly recommended.';
$string['posteronsmallscreens'] = 'Poster only on small screens';
$string['posteronsmallscreens_desc'] = 'On screens narrower than 768 pixels (most phones), show the poster image instead of loading the video. This saves mobile data.';
$string['privacy:metadata'] = 'The Background video plugin does not store any personal data. If a YouTube, Vimeo or externally hosted video is used, visitors\' browsers connect to that service to play it.';
$string['respectreducedmotion'] = 'Respect the reduce-motion preference';
$string['respectreducedmotion_desc'] = 'Do not play the video for visitors whose device asks websites to reduce motion; they see the poster image instead. Many people set this, sometimes without knowing: for example, turning off animation effects in Windows turns it on. Some people set it because motion on screen makes them unwell, so leaving this on is the more accessible choice.';
$string['soundbutton'] = 'Background video sound';
$string['source'] = 'Video source';
$string['source_default'] = 'Use the default video';
$string['source_desc'] = 'Use a video address (YouTube, Vimeo or a direct video file link), or a video file uploaded here.';
$string['source_file'] = 'Uploaded file';
$string['source_url'] = 'Video address (URL)';
$string['sourceheading'] = 'Video';
$string['textcolour'] = 'Text colour over the video';
$string['textcolour_dark'] = 'Dark';
$string['textcolour_desc'] = 'Colour of the page title, breadcrumbs and page tabs, which sit directly on the video. Light suits a dark tint and Dark suits a light tint. Theme default leaves the theme\'s colours unchanged.';
$string['textcolour_light'] = 'Light';
$string['textcolour_theme'] = 'Theme default';
$string['usedefault'] = 'Use default';
$string['videofile'] = 'Video file';
$string['videofile_desc'] = 'An MP4 (H.264) file plays in every current browser. WebM is usually smaller. The file is served to everyone, including visitors who are not logged in, so do not upload anything private.';
$string['videourl'] = 'Video address';
$string['videourl_desc'] = 'Paste a normal YouTube or Vimeo video address (for example https://www.youtube.com/watch?v=...), or a direct link to an .mp4, .m4v, .webm or .ogv file.
<ul>
<li>YouTube videos play through youtube-nocookie.com. YouTube may briefly show its title or logo when the video starts and loops; this cannot be fully prevented.</li>
<li>Vimeo videos use Vimeo\'s background player, which hides the controls. It needs a video on a paid Vimeo plan; on a free plan the player shows its controls.</li>
<li>Privacy: with YouTube, Vimeo or a direct link to a file on another website, every visitor\'s browser, including on the login page before they log in, connects to that service, which can see their IP address. Consider this against your privacy policy, or upload a file instead.</li>
</ul>';
