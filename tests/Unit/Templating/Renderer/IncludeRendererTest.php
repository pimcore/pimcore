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

namespace Pimcore\Tests\Unit\Templating\Renderer;

use PHPUnit\Framework\TestCase;
use Pimcore\Templating\Renderer\ActionRenderer;
use Pimcore\Templating\Renderer\IncludeRenderer;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class IncludeRendererTest extends TestCase
{
    public function testRenderReturnsEmptyContentForMissingNumericStringDocument(): void
    {
        $renderer = new IncludeRenderer(
            $this->createStub(ActionRenderer::class),
            $this->createStub(EventDispatcherInterface::class),
        );

        self::assertSame('', $renderer->render('0', cacheEnabled: false));
    }
}
