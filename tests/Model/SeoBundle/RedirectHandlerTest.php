<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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

        $request = new Request();
        $request->server->set('REQUEST_URI', 'http://example.org/source');
        $response = $redirectHandler->checkForRedirect($request);

        $this->assertTrue($response->isRedirect(), 'Redirect because redirect source and request path match');
        $this->assertEquals('http://example.org/target', $response->headers->get('Location'), 'Redirect target should be /target');

        $request = new Request();
        $request->server->set('REQUEST_URI', 'http://example.org/other_source');
        $response = $redirectHandler->checkForRedirect($request);
        $this->assertFalse($response->isRedirect(), 'Redirected althouhg path did not match');
    }

    public function testRedirectWithSourceSite(): void
    {
        $siteResolver = Pimcore::getContainer()->get(Pimcore\Http\Request\Resolver\SiteResolver::class);
        $request = new Request();
        $request->server->set('REQUEST_URI', 'http://example.org/source');
        $request->attributes->set(Pimcore\Http\Request\Resolver\SiteResolver::ATTRIBUTE_SITE, 1);

        $site = new Pimcore\Model\Site();
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
        $this->assertEquals('http://example.org/target', $response->headers->get('Location'));

        $request = new Request();
        $request->server->set('REQUEST_URI', 'http://example.org/source');
        $response = $redirectHandler->checkForRedirect($request);
        $this->assertFalse($response->isRedirect(), 'Redirected although source site does not match');
    }
}
