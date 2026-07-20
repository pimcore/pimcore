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

namespace Pimcore\Tests\Unit\SimpleBackendSearchBundle\Controller;

use Pimcore\Bundle\SimpleBackendSearchBundle\Controller\DataObjectController;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class DataObjectControllerTest extends TestCase
{
    /**
     * Exposes the protected getRequestParameter() helper without needing the
     * full HTTP/DB stack the optionsAction() otherwise requires.
     */
    private function controller(): DataObjectController
    {
        return new class extends DataObjectController {
            public function __construct()
            {
            }

            public function readParameter(Request $request, string $key): string
            {
                return $this->getRequestParameter($request, $key);
            }
        };
    }

    public function testPrefersPostBodyOverQueryString(): void
    {
        $controller = $this->controller();

        // Regression for Error 414: large parameters are now sent via the POST
        // body, which must take precedence over the GET query string.
        $request = new Request(query: ['fieldConfig' => 'fromGet'], request: ['fieldConfig' => 'fromPost']);

        $this->assertSame('fromPost', $controller->readParameter($request, 'fieldConfig'));
    }

    public function testFallsBackToQueryStringWhenPostIsAbsent(): void
    {
        $controller = $this->controller();

        // Backward compatibility: existing GET callers must keep working.
        $request = new Request(query: ['data' => 'fromGet']);

        $this->assertSame('fromGet', $controller->readParameter($request, 'data'));
    }

    public function testUsesPostBodyWhenQueryStringIsAbsent(): void
    {
        $controller = $this->controller();

        $request = new Request(request: ['unsavedChanges' => 'fromPost']);

        $this->assertSame('fromPost', $controller->readParameter($request, 'unsavedChanges'));
    }

    public function testReturnsEmptyStringWhenParameterIsMissingEverywhere(): void
    {
        $controller = $this->controller();

        $this->assertSame('', $controller->readParameter(new Request(), 'fieldConfig'));
    }
}
