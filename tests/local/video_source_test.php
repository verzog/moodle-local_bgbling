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

/**
 * Tests for video_source URL parsing.
 *
 * @package    local_bgbling
 * @copyright  2026 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(video_source::class)]
final class video_source_test extends \basic_testcase {
    /**
     * Accepted URLs and what they should become.
     *
     * @return array
     */
    public static function valid_url_provider(): array {
        $yt = 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?autoplay=1&mute=1&loop=1&playlist=dQw4w9WgXcQ'
            . '&controls=0&playsinline=1&disablekb=1&fs=0&iv_load_policy=3&rel=0';
        return [
            'YouTube watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', video_source::TYPE_YOUTUBE, $yt],
            'YouTube watch, extra params' => [
                'https://youtube.com/watch?feature=share&v=dQw4w9WgXcQ&t=10s',
                video_source::TYPE_YOUTUBE,
                $yt,
            ],
            'YouTube mobile' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ', video_source::TYPE_YOUTUBE, $yt],
            'YouTube short link' => ['https://youtu.be/dQw4w9WgXcQ?si=abc', video_source::TYPE_YOUTUBE, $yt],
            'YouTube embed' => ['https://www.youtube.com/embed/dQw4w9WgXcQ', video_source::TYPE_YOUTUBE, $yt],
            'YouTube shorts' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ', video_source::TYPE_YOUTUBE, $yt],
            'YouTube over http' => ['http://www.youtube.com/watch?v=dQw4w9WgXcQ', video_source::TYPE_YOUTUBE, $yt],
            'YouTube nocookie' => ['https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', video_source::TYPE_YOUTUBE, $yt],
            'Vimeo' => [
                'https://vimeo.com/76979871',
                video_source::TYPE_VIMEO,
                'https://player.vimeo.com/video/76979871?background=1&dnt=1',
            ],
            'Vimeo unlisted' => [
                'https://vimeo.com/76979871/0a1b2c3d4e',
                video_source::TYPE_VIMEO,
                'https://player.vimeo.com/video/76979871?background=1&dnt=1&h=0a1b2c3d4e',
            ],
            'Vimeo channel' => [
                'https://vimeo.com/channels/staffpicks/76979871',
                video_source::TYPE_VIMEO,
                'https://player.vimeo.com/video/76979871?background=1&dnt=1',
            ],
            'Vimeo player with hash' => [
                'https://player.vimeo.com/video/76979871?h=0a1b2c3d4e&autoplay=0',
                video_source::TYPE_VIMEO,
                'https://player.vimeo.com/video/76979871?background=1&dnt=1&h=0a1b2c3d4e',
            ],
            'Direct MP4' => [
                'https://cdn.example.com/media/loop.mp4',
                video_source::TYPE_DIRECT,
                'https://cdn.example.com/media/loop.mp4',
            ],
            'Direct WebM, upper case, query' => [
                'https://cdn.example.com/media/LOOP.WEBM?v=2',
                video_source::TYPE_DIRECT,
                'https://cdn.example.com/media/LOOP.WEBM?v=2',
            ],
        ];
    }

    /**
     * Accepted URLs build the expected source.
     *
     * @param string $input URL as pasted by the admin.
     * @param string $type Expected type.
     * @param string $expected Expected URL the browser loads.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('valid_url_provider')]
    public function test_valid_urls(string $input, string $type, string $expected): void {
        $source = video_source::from_url($input);
        $this->assertNotNull($source);
        $this->assertSame($type, $source->type);
        $this->assertSame($expected, $source->url->out(false));
        $this->assertSame($type === video_source::TYPE_DIRECT, $source->is_native());
    }

    /**
     * Rejected URLs.
     *
     * @return array
     */
    public static function invalid_url_provider(): array {
        return [
            'Empty' => [''],
            'Not a URL' => ['hello'],
            'JavaScript' => ['javascript:alert(1)//.mp4'],
            'FTP' => ['ftp://example.com/loop.mp4'],
            'Data URI' => ['data:video/mp4;base64,AAAA'],
            'Web page' => ['https://example.com/page.html'],
            'No extension' => ['https://example.com/video'],
            'YouTube channel' => ['https://www.youtube.com/@moodle'],
            'YouTube playlist' => ['https://www.youtube.com/playlist?list=PL123'],
            'YouTube bad ID' => ['https://www.youtube.com/watch?v=short'],
            'YouTube ID injection' => ['https://youtu.be/dQw4w9WgXcQ"><script>'],
            'Vimeo non-numeric' => ['https://vimeo.com/about'],
            'Vimeo bad hash' => ['https://player.vimeo.com/video/76979871?h=zz"x'],
            'HTTP file on an HTTPS site' => ['http://cdn.example.com/media/loop.mp4'],
            'Lookalike host' => ['https://youtube.com.example.com/watch?v=dQw4w9WgXcQ'],
        ];
    }

    /**
     * Rejected URLs return null.
     *
     * @param string $input URL as pasted by the admin.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalid_url_provider')]
    public function test_invalid_urls(string $input): void {
        $this->assertNull(video_source::from_url($input));
    }
}
