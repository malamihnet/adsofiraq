<?php

namespace Tests\Unit;

use App\Services\VideoUrlParser;
use PHPUnit\Framework\TestCase;

class VideoUrlParserTest extends TestCase
{
    public function test_parses_vimeo_player_embed_url(): void
    {
        $parsed = VideoUrlParser::parse('https://player.vimeo.com/video/1174674945');

        $this->assertNotNull($parsed);
        $this->assertSame('vimeo', $parsed['provider']);
        $this->assertSame('1174674945', $parsed['video_id']);
        $this->assertSame('https://player.vimeo.com/video/1174674945', $parsed['embed_url']);
    }

    public function test_parses_youtube_watch_url(): void
    {
        $parsed = VideoUrlParser::parse('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

        $this->assertNotNull($parsed);
        $this->assertSame('youtube', $parsed['provider']);
        $this->assertSame('https://www.youtube.com/embed/dQw4w9WgXcQ', $parsed['embed_url']);
    }
}
