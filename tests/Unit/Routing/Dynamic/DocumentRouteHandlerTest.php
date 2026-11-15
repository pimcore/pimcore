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

namespace Pimcore\Tests\Unit\Routing\Dynamic;

use Pimcore\Config;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Http\Request\Resolver\StaticPageResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Page;
use Pimcore\Routing\Dynamic\DocumentRouteHandler;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tool\Console;
use Symfony\Component\HttpFoundation\Request;

/**
 * pimcore/pimcore#19477: the route of a page uses the document's controller, which is document data,
 * so it has to let Symfony's controller resolver accept registered controllers only.
 */
final class DocumentRouteHandlerTest extends TestCase
{
    public function testPageRouteOnlyAllowsRegisteredControllers(): void
    {
        $page = $this->createMock(Page::class);
        $page->method('getType')->willReturn('page');
        $page->method('getFullPath')->willReturn('/en/page');
        $page->method('isPublished')->willReturn(true);
        $page->method('getController')->willReturn(Console::class . '::execInBackground');

        $requestHelper = $this->createMock(RequestHelper::class);
        $requestHelper->method('getMainRequest')->willReturn(Request::create('/en/page'));
        $requestHelper->method('isFrontendRequestByAdmin')->willReturn(false);

        $handler = new DocumentRouteHandler(
            $this->createMock(Document\Service::class),
            $this->createMock(SiteResolver::class),
            $requestHelper,
            new Config(),
            $this->createMock(StaticPageResolver::class)
        );

        $route = $handler->buildRouteForDocument($page);

        self::assertNotNull($route);
        self::assertSame(Console::class . '::execInBackground', $route->getDefault('_controller'));
        self::assertTrue($route->getDefault('_check_controller_is_allowed'));
    }
}
