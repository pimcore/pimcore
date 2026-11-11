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
            // Config is final and cannot be mocked; the redirect handler does not read it
            new Config()
        );
    }

    private function invokeRedirectHandler(RoutingListener $listener, string $path, string $httpMethod = 'GET'): RequestEvent
    {
        // a real request carries these characters percent-encoded (Symfony rejects them raw);
        // the listener then works on urldecode($request->getPathInfo()), as done here
        $request = Request::create(
            strtr($path, ['\\' => '%5C', "\t" => '%09', "\n" => '%0A', "\r" => '%0D']),
            $httpMethod
        );
        $path = urldecode($request->getPathInfo());
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

    public function testTabsAndNewlinesBetweenLeadingSlashesAreRemoved(): void
    {
        // the listener works on the url-decoded path, and browsers ignore tab and newline
        // characters in a URL, so they must not keep two leading slashes apart
        foreach (["/app.php/\t/evil.com", "/app.php/\\\t\\evil.com", "/app.php/\t\t//evil.com"] as $path) {
            $location = $this->invokeRedirectHandler($this->makeListener(), $path)->getResponse()->headers->get('Location');
            $this->assertSame('/evil.com', $location, json_encode($path));
        }

        // a newline ends the path match, so the redirect target is cut off there
        $location = $this->invokeRedirectHandler($this->makeListener(), "/app.php/\n/evil.com")->getResponse()->headers->get('Location');
        $this->assertStringStartsNotWith('//', $location);
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
