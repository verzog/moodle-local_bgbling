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

namespace local_bgbling\local;

use moodle_url;

/**
 * A parsed, validated video source.
 *
 * Admins paste an ordinary YouTube, Vimeo or direct file URL. This class works out which
 * kind it is and builds the URL the page will actually load. For YouTube and Vimeo only the
 * video ID (and Vimeo privacy hash) is taken from the pasted URL; the embed URL is always
 * built here, so the plugin never outputs an arbitrary iframe source.
 *
 * @package    local_bgbling
 * @copyright  2026 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class video_source {
    /** @var string A video file uploaded to the plugin settings. */
    public const TYPE_FILE = 'file';

    /** @var string A direct link to an MP4, WebM or Ogg video file. */
    public const TYPE_DIRECT = 'direct';

    /** @var string A YouTube video, shown through youtube-nocookie.com. */
    public const TYPE_YOUTUBE = 'youtube';

    /** @var string A Vimeo video, shown with the background player. */
    public const TYPE_VIMEO = 'vimeo';

    /** @var string[] Accepted direct file extensions and their MIME types. */
    public const DIRECT_TYPES = [
        'mp4' => 'video/mp4',
        'm4v' => 'video/mp4',
        'webm' => 'video/webm',
        'ogv' => 'video/ogg',
    ];

    /**
     * Constructor; use the static factory methods instead.
     *
     * @param string $type One of the TYPE_ constants.
     * @param moodle_url $url The URL the browser loads (video file or iframe embed).
     * @param string $mimetype MIME type for native video, empty for iframe embeds.
     */
    private function __construct(
        /** @var string One of the TYPE_ constants. */
        public readonly string $type,
        /** @var moodle_url The URL the browser loads. */
        public readonly moodle_url $url,
        /** @var string MIME type for native video, empty for iframe embeds. */
        public readonly string $mimetype = '',
    ) {
    }

    /**
     * Whether this source plays in a native video element (rather than an iframe).
     *
     * @return bool
     */
    public function is_native(): bool {
        return $this->type === self::TYPE_FILE || $this->type === self::TYPE_DIRECT;
    }

    /**
     * Builds a source for a file uploaded to the plugin settings.
     *
     * @param moodle_url $url The pluginfile URL.
     * @param string $mimetype The stored file's MIME type.
     * @return self
     */
    public static function from_file(moodle_url $url, string $mimetype): self {
        return new self(self::TYPE_FILE, $url, $mimetype);
    }

    /**
     * Parses an admin-supplied URL.
     *
     * @param string $url A YouTube, Vimeo or direct video file URL.
     * @return self|null The source, or null if the URL is not one we accept.
     */
    public static function from_url(string $url): ?self {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        $parts = parse_url($url);
        if (empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }
        $scheme = strtolower($parts['scheme']);
        if ($scheme !== 'https' && $scheme !== 'http') {
            return null;
        }
        $host = strtolower($parts['host']);
        $path = $parts['path'] ?? '';
        $query = [];
        parse_str($parts['query'] ?? '', $query);

        $youtubeid = self::youtube_id($host, $path, $query);
        if ($youtubeid !== null) {
            return self::youtube($youtubeid);
        }
        if (self::is_vimeo_host($host)) {
            return self::vimeo($host, $path, $query);
        }
        if (self::is_youtube_host($host)) {
            // A YouTube address we could not get a video ID from (a channel, a playlist and so on).
            return null;
        }
        if ($scheme === 'http' && self::site_uses_https()) {
            // Browsers block http media on an https page (mixed content), so it would never play.
            return null;
        }
        return self::direct($url, $path);
    }

    /**
     * Whether the Moodle site is served over https.
     *
     * @return bool
     */
    private static function site_uses_https(): bool {
        global $CFG;
        return str_starts_with(strtolower($CFG->wwwroot), 'https://');
    }

    /**
     * Whether the host belongs to YouTube.
     *
     * @param string $host Lower-case host name.
     * @return bool
     */
    private static function is_youtube_host(string $host): bool {
        return in_array($host, [
            'youtube.com',
            'www.youtube.com',
            'm.youtube.com',
            'youtu.be',
            'youtube-nocookie.com',
            'www.youtube-nocookie.com',
        ], true);
    }

    /**
     * Whether the host belongs to Vimeo.
     *
     * @param string $host Lower-case host name.
     * @return bool
     */
    private static function is_vimeo_host(string $host): bool {
        return in_array($host, ['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'], true);
    }

    /**
     * Extracts an 11-character YouTube video ID.
     *
     * @param string $host Lower-case host name.
     * @param string $path URL path.
     * @param array $query Parsed query string.
     * @return string|null
     */
    private static function youtube_id(string $host, string $path, array $query): ?string {
        if (!self::is_youtube_host($host)) {
            return null;
        }
        $candidate = null;
        if ($host === 'youtu.be') {
            $candidate = trim($path, '/');
        } else if ($path === '/watch' && isset($query['v']) && is_string($query['v'])) {
            $candidate = $query['v'];
        } else if (preg_match('~^/(?:embed|shorts|live|v)/([^/]+)/?$~', $path, $matches)) {
            $candidate = $matches[1];
        }
        if ($candidate !== null && preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate)) {
            return $candidate;
        }
        return null;
    }

    /**
     * Builds a YouTube background embed.
     *
     * The playlist parameter set to the same ID is what makes a single video loop.
     *
     * @param string $id Validated YouTube video ID.
     * @return self
     */
    private static function youtube(string $id): self {
        $url = new moodle_url('https://www.youtube-nocookie.com/embed/' . $id, [
            'autoplay' => 1,
            'mute' => 1,
            'loop' => 1,
            'playlist' => $id,
            'controls' => 0,
            'playsinline' => 1,
            'disablekb' => 1,
            'fs' => 0,
            'iv_load_policy' => 3,
            'rel' => 0,
        ]);
        return new self(self::TYPE_YOUTUBE, $url);
    }

    /**
     * Builds a Vimeo background embed.
     *
     * Accepts vimeo.com/ID, vimeo.com/ID/HASH (unlisted), vimeo.com/channels/NAME/ID,
     * player.vimeo.com/video/ID and the ?h=HASH form.
     *
     * @param string $host Lower-case host name.
     * @param string $path URL path.
     * @param array $query Parsed query string.
     * @return self|null
     */
    private static function vimeo(string $host, string $path, array $query): ?self {
        if ($host === 'player.vimeo.com') {
            $pattern = '~^/video/(\d+)/?$~';
        } else {
            $pattern = '~^/(?:channels/[^/]+/|groups/[^/]+/videos/)?(\d+)(?:/([0-9a-f]+))?/?$~';
        }
        if (!preg_match($pattern, $path, $matches)) {
            return null;
        }
        $params = [
            'background' => 1,
            'dnt' => 1,
        ];
        $hash = $matches[2] ?? '';
        if ($hash === '' && isset($query['h']) && is_string($query['h'])) {
            $hash = $query['h'];
        }
        if ($hash !== '') {
            if (!preg_match('/^[0-9a-f]+$/', $hash)) {
                return null;
            }
            $params['h'] = $hash;
        }
        $url = new moodle_url('https://player.vimeo.com/video/' . $matches[1], $params);
        return new self(self::TYPE_VIMEO, $url);
    }

    /**
     * Builds a direct video file source, checking the file extension.
     *
     * @param string $url The full URL as entered.
     * @param string $path URL path.
     * @return self|null
     */
    private static function direct(string $url, string $path): ?self {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!isset(self::DIRECT_TYPES[$extension])) {
            return null;
        }
        $cleaned = clean_param($url, PARAM_URL);
        if ($cleaned === '') {
            return null;
        }
        return new self(self::TYPE_DIRECT, new moodle_url($cleaned), self::DIRECT_TYPES[$extension]);
    }
}
