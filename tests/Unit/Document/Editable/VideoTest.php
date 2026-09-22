<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Unit\Document\Editable;

use Pimcore\Model\Document\Editable\Video;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionProperty;

/**
 * Regression test for GHSA-mvh8-52hw-jrch: a document-edit user could store a YouTube/Dailymotion
 * id containing a double quote, which broke out of the `<iframe src="...">` attribute of the
 * guest-rendered frontend output and injected an event-handler attribute (stored XSS), since the
 * id was concatenated into the markup unescaped.
 */
final class VideoTest extends TestCase
{
    private const XSS_PAYLOAD = 'x" onload="alert(document.domain)" data-x="';

    protected function needsDb(): bool
    {
        return false;
    }

    public function testYoutubeFrontendEscapesIdContainingDoubleQuote(): void
    {
        $video = $this->buildVideo(Video::TYPE_YOUTUBE, self::XSS_PAYLOAD);

        $rendered = $video->frontend();

        $this->assertStringNotContainsString('onload="alert(document.domain)"', $rendered);
        $this->assertStringContainsString(
            htmlspecialchars(self::XSS_PAYLOAD, ENT_QUOTES, 'UTF-8'),
            $rendered
        );
    }

    public function testYoutubeFrontendStillRendersALegitimateId(): void
    {
        $video = $this->buildVideo(Video::TYPE_YOUTUBE, 'dQw4w9WgXcQ');

        $rendered = $video->frontend();

        $this->assertStringContainsString(
            'src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?wmode=transparent"',
            $rendered
        );
    }

    public function testDailymotionFrontendEscapesIdContainingDoubleQuote(): void
    {
        $video = $this->buildVideo(Video::TYPE_DAILYMOTION, self::XSS_PAYLOAD);

        $rendered = $video->frontend();

        $this->assertStringNotContainsString('onload="alert(document.domain)"', $rendered);
        $this->assertStringContainsString(
            htmlspecialchars(self::XSS_PAYLOAD, ENT_QUOTES, 'UTF-8'),
            $rendered
        );
    }

    public function testDailymotionFrontendStillRendersALegitimateId(): void
    {
        $video = $this->buildVideo(Video::TYPE_DAILYMOTION, 'x2u2vsp');

        $rendered = $video->frontend();

        $this->assertStringContainsString(
            'src="https://www.dailymotion.com/embed/video/x2u2vsp?"',
            $rendered
        );
    }

    /**
     * Builds a Video editable with the given type/id without going through
     * setDataFromEditmode(), which would otherwise resolve the id against the Asset backend.
     */
    private function buildVideo(string $type, string $id): Video
    {
        $video = new Video();
        $video->setName('video');
        (new ReflectionProperty($video, 'type'))->setValue($video, $type);
        (new ReflectionProperty($video, 'id'))->setValue($video, $id);

        return $video;
    }
}
