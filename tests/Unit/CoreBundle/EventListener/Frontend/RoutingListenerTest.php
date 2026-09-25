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

namespace Pimcore\Tests\Unit\CoreBundle\EventListener\Frontend;

use Pimcore\Bundle\CoreBundle\EventListener\Frontend\RoutingListener;
use Pimcore\Config;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class RoutingListenerTest extends TestCase
{
    private function makeListener(): RoutingListener
    {
        return new RoutingListener(
            $this->createMock(RequestHelper::class),
            $this->createMock(SiteResolver::class),
            $this->createMock(Config::class)
        );
    }

    private function invokeRedirectHandler(RoutingListener $listener, string $path, string $httpMethod = 'GET'): RequestEvent
    {
        $request = Request::create($path, $httpMethod);
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            KernelInterface::MAIN_REQUEST
        );

        $method = new ReflectionMethod($listener, 'handleFrontControllerRedirect');
        $method->invoke($listener, $event, $path);

        return $event;
    }

    public function testBackslashAfterAppPhpDoesNotProduceSchemeRelativeRedirect(): void
    {
        // GET /app.php/\evil.com must not redirect to a location browsers treat as
        // "//evil.com" (backslash is normalized to "/" for special-scheme URLs).
        $event = $this->invokeRedirectHandler($this->makeListener(), '/app.php/\\evil.com');

        $this->assertTrue($event->hasResponse());
        $location = $event->getResponse()->headers->get('Location');
        $this->assertSame('/evil.com', $location);
        $this->assertStringStartsNotWith('//', $location);
        $this->assertStringStartsNotWith('/\\', $location);
    }

    public function testMultipleLeadingSlashesAndBackslashesAreAllStripped(): void
    {
        $event = $this->invokeRedirectHandler($this->makeListener(), '/app.php/\\/\\evil.com');

        $location = $event->getResponse()->headers->get('Location');
        $this->assertSame('/evil.com', $location);
    }

    public function testLegitimateAppPhpPathIsRedirectedWithoutAppPhpPrefix(): void
    {
        $event = $this->invokeRedirectHandler($this->makeListener(), '/app.php/en/products/item-1');

        $this->assertTrue($event->hasResponse());
        $response = $event->getResponse();
        $this->assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
        $this->assertSame('/en/products/item-1', $response->headers->get('Location'));
    }

    public function testPathWithoutAppPhpIsNotTouched(): void
    {
        $event = $this->invokeRedirectHandler($this->makeListener(), '/en/products/item-1');

        $this->assertFalse($event->hasResponse());
    }

    public function testNonGetMethodIsNotRedirected(): void
    {
        $event = $this->invokeRedirectHandler($this->makeListener(), '/app.php/en/products/item-1', 'POST');

        $this->assertFalse($event->hasResponse());
    }
}
