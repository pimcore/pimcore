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

namespace Pimcore\Tests\Unit\Http;

use Pimcore\Http\RequestHelper;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RequestContext;

/**
 * Regression test for GHSA-4f8h-wfx8-wwmx (unauthenticated query parameter disables multi-site
 * isolation and site resolution).
 */
class RequestHelperTest extends TestCase
{
    private function buildRequestHelper(): RequestHelper
    {
        return new RequestHelper(new RequestStack(), new RequestContext());
    }

    /**
     * isFrontendRequestByAdmin() only checks for the presence of the query parameter, with no
     * session involved at all, by design (it also gates purely cosmetic behavior that must work
     * before any session is available). Callers that need a real security decision must not use
     * it on its own.
     *
     * @dataProvider adminQueryParamProvider
     */
    public function testIsFrontendRequestByAdminIsTrueForAnyRequestCarryingTheParam(string $param): void
    {
        $request = Request::create('http://example.com/some/page', 'GET', [$param => '1']);

        $this->assertTrue($this->buildRequestHelper()->isFrontendRequestByAdmin($request));
    }

    /**
     * The authenticated variant must reject the very same request that isFrontendRequestByAdmin()
     * accepts, because it carries no session at all. Without this check, e.g. site resolution and
     * site-membership enforcement could be disabled by anyone simply by adding the query
     * parameter to the URL.
     *
     * @dataProvider adminQueryParamProvider
     */
    public function testIsAuthenticatedFrontendRequestByAdminRejectsRequestWithoutSession(string $param): void
    {
        $request = Request::create('http://example.com/some/page', 'GET', [$param => '1']);

        $this->assertFalse($this->buildRequestHelper()->isAuthenticatedFrontendRequestByAdmin($request));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function adminQueryParamProvider(): array
    {
        return [
            'pimcore_editmode' => ['pimcore_editmode'],
            'pimcore_preview' => ['pimcore_preview'],
            'pimcore_admin' => ['pimcore_admin'],
            'pimcore_object_preview' => ['pimcore_object_preview'],
            'pimcore_version' => ['pimcore_version'],
        ];
    }

    public function testNeitherMethodTriggersOnAPlainFrontendRequest(): void
    {
        $request = Request::create('http://example.com/some/page');

        $requestHelper = $this->buildRequestHelper();

        $this->assertFalse($requestHelper->isFrontendRequestByAdmin($request));
        $this->assertFalse($requestHelper->isAuthenticatedFrontendRequestByAdmin($request));
    }
}
