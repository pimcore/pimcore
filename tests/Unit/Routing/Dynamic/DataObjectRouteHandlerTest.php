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

use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\DataObject;
use Pimcore\Routing\DataObjectRoute;
use Pimcore\Routing\Dynamic\DataObjectRouteHandler;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tool\Console;
use ReflectionMethod;

/**
 * pimcore/pimcore#19477: the route of a data object slug uses the action of the urlslug field
 * definition, which is class definition data, so it has to let Symfony's controller resolver
 * accept registered controllers only.
 */
final class DataObjectRouteHandlerTest extends TestCase
{
    public function testSlugRouteOnlyAllowsRegisteredControllers(): void
    {
        $slug = $this->createMock(DataObject\Data\UrlSlug::class);
        $slug->method('getSlug')->willReturn('/en/product');
        $slug->method('getAction')->willReturn(Console::class . '::execInBackground');
        $slug->method('getOwnertype')->willReturn('object');

        $handler = new DataObjectRouteHandler(
            $this->createMock(SiteResolver::class),
            $this->createMock(RequestHelper::class)
        );

        $route = (new ReflectionMethod($handler, 'buildRouteForFromSlug'))
            ->invoke($handler, $slug, $this->createMock(DataObject\Concrete::class));

        self::assertInstanceOf(DataObjectRoute::class, $route);
        self::assertSame(Console::class . '::execInBackground', $route->getDefault('_controller'));
        self::assertTrue($route->getDefault('_check_controller_is_allowed'));
    }
}
