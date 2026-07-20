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
}
