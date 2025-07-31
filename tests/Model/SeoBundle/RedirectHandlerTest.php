<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Model\SeoBundle;

use Pimcore;
use Pimcore\Bundle\SeoBundle\Redirect\RedirectHandler;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;

class RedirectHandlerTest extends TestCase
{
    protected function needsDb(): bool
    {
        return true;
    }

    public function testRedirectAllSites(): void
    {
        $redirect = new Pimcore\Bundle\SeoBundle\Model\Redirect();
        $redirect->setType(Pimcore\Bundle\SeoBundle\Model\Redirect::TYPE_PATH);
        $redirect->setSource('/source');
        $redirect->setTarget('/target');
        $redirect->save();

        /** @var RedirectHandler $redirectHandler */
        $redirectHandler = Pimcore::getContainer()->get(RedirectHandler::class);

        $request = Request::create('http://example.org/source', 'GET');
        $response = $redirectHandler->checkForRedirect($request);

        $this->assertTrue($response->isRedirect(), 'Redirect because redirect source and request path match');
        $this->assertEquals('/target', $response->headers->get('Location'), 'Redirect target should be /target');

        $request = Request::create('http://example.org/other_source', 'GET');
        $response = $redirectHandler->checkForRedirect($request);
        $this->assertNull($response, 'Redirected although path did not match');

        $redirect->delete();
    }

    public function testRedirectWithSourceSite(): void
    {
        $siteResolver = Pimcore::getContainer()->get(Pimcore\Http\Request\Resolver\SiteResolver::class);
        $request = Request::create('http://example.org/source', 'GET');
        $request->attributes->set(Pimcore\Http\Request\Resolver\SiteResolver::ATTRIBUTE_SITE, 1);

        $site = new Pimcore\Model\Site();
        $site->save();
        $siteResolver->setSite($request, $site);

        $redirect = new Pimcore\Bundle\SeoBundle\Model\Redirect();
        $redirect->setType(Pimcore\Bundle\SeoBundle\Model\Redirect::TYPE_PATH);
        $redirect->setSource('/source');
        $redirect->setSourceSite($site->getId());
        $redirect->setTarget('/target');
        $redirect->save();

        /** @var RedirectHandler $redirectHandler */
        $redirectHandler = Pimcore::getContainer()->get(RedirectHandler::class);

        $response = $redirectHandler->checkForRedirect($request);

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/target', $response->headers->get('Location'));

        $request = Request::create('http://example.org/source', 'GET');
        $otherSite = new Pimcore\Model\Site();
        $otherSite->save();
        $siteResolver->setSite($request, $otherSite);
        $response = $redirectHandler->checkForRedirect($request);
        $this->assertNull($response, 'Redirected although source site does not match');

        $redirect->delete();
        $site->delete();
        $otherSite->delete();
    }

    public function testRedirectWithMigratedSourceSite(): void
    {
        // Test for issue #18582: Migration Version20250526125951 makes existing redirects unreachable
        // Create a redirect with sourceSite=0 (as would be set by the migration)
        $redirect = new Pimcore\Bundle\SeoBundle\Model\Redirect();
        $redirect->setType(Pimcore\Bundle\SeoBundle\Model\Redirect::TYPE_PATH);
        $redirect->setSource('/migrated-source');
        $redirect->setSourceSite(0);  // This simulates a redirect migrated from NULL to 0
        $redirect->setTarget('/migrated-target');
        $redirect->save();

        /** @var RedirectHandler $redirectHandler */
        $redirectHandler = Pimcore::getContainer()->get(RedirectHandler::class);

        // Request without site context should find the redirect with sourceSite=0
        $request = Request::create('http://example.org/migrated-source', 'GET');
        $response = $redirectHandler->checkForRedirect($request);

        $this->assertNotNull($response, 'Should find redirect with sourceSite=0 when no site is specified');
        $this->assertTrue($response->isRedirect(), 'Response should be a redirect');
        $this->assertEquals('/migrated-target', $response->headers->get('Location'), 'Should redirect to correct target');

        $redirect->delete();
    }

    public function testRedirectWithNullSourceSite(): void
    {
        // Test that redirects with NULL sourceSite still work (backward compatibility)
        $redirect = new Pimcore\Bundle\SeoBundle\Model\Redirect();
        $redirect->setType(Pimcore\Bundle\SeoBundle\Model\Redirect::TYPE_PATH);
        $redirect->setSource('/null-source');
        $redirect->setSourceSite(null);  // Explicitly set to null
        $redirect->setTarget('/null-target');
        $redirect->save();

        /** @var RedirectHandler $redirectHandler */
        $redirectHandler = Pimcore::getContainer()->get(RedirectHandler::class);

        // Request without site context should find the redirect with sourceSite=NULL
        $request = Request::create('http://example.org/null-source', 'GET');
        $response = $redirectHandler->checkForRedirect($request);

        $this->assertNotNull($response, 'Should find redirect with sourceSite=NULL when no site is specified');
        $this->assertTrue($response->isRedirect(), 'Response should be a redirect');
        $this->assertEquals('/null-target', $response->headers->get('Location'), 'Should redirect to correct target');

        $redirect->delete();
    }
}
