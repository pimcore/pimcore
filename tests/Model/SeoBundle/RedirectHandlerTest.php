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
use Pimcore\Bundle\SeoBundle\Model\Redirect;
use Pimcore\Bundle\SeoBundle\Redirect\RedirectHandler;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Support\Util\TestHelper;
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

    public function testRedirectToDocumentTarget(): void
    {
        $document = TestHelper::createEmptyDocumentPage();

        $redirect = new Redirect();
        $redirect->setType(Redirect::TYPE_PATH);
        $redirect->setSource('/document-source');
        $redirect->setTarget((string) $document->getId());
        $redirect->setTargetType(Redirect::TARGET_TYPE_DOCUMENT);
        $redirect->save();

        $response = $this->getRedirectHandler()->checkForRedirect(Request::create('http://example.org/document-source', 'GET'));

        $this->assertNotNull($response);
        $this->assertTrue($response->isRedirect());
        $this->assertSame($document->getFullPath(), $response->headers->get('Location'));

        $redirect->delete();
        $document->delete();
    }

    public function testRedirectToLegacyNumericDocumentTargetWithoutTargetType(): void
    {
        // legacy rows (and auto-created redirects) store a numeric document ID without a target type;
        // they must still resolve to the document, see #18293 review feedback
        $document = TestHelper::createEmptyDocumentPage();

        $redirect = new Redirect();
        $redirect->setType(Redirect::TYPE_PATH);
        $redirect->setSource('/legacy-source');
        $redirect->setTarget((string) $document->getId());
        $redirect->save();

        $response = $this->getRedirectHandler()->checkForRedirect(Request::create('http://example.org/legacy-source', 'GET'));

        $this->assertNotNull($response, 'legacy numeric document target must still resolve');
        $this->assertTrue($response->isRedirect());
        $this->assertSame($document->getFullPath(), $response->headers->get('Location'));

        $redirect->delete();
        $document->delete();
    }

    public function testRedirectToAssetTarget(): void
    {
        $asset = TestHelper::createImageAsset();

        $redirect = new Redirect();
        $redirect->setType(Redirect::TYPE_PATH);
        $redirect->setSource('/asset-source');
        $redirect->setTarget((string) $asset->getId());
        $redirect->setTargetType(Redirect::TARGET_TYPE_ASSET);
        $redirect->save();

        $response = $this->getRedirectHandler()->checkForRedirect(Request::create('http://example.org/asset-source', 'GET'));

        $this->assertNotNull($response);
        $this->assertTrue($response->isRedirect());
        $this->assertSame($asset->getFullPath(), $response->headers->get('Location'));

        $redirect->delete();
        $asset->delete();
    }

    public function testMissingTargetDoesNotRedirect(): void
    {
        $redirect = new Redirect();
        $redirect->setType(Redirect::TYPE_PATH);
        $redirect->setSource('/missing-source');
        $redirect->setTarget('999999999');
        $redirect->setTargetType(Redirect::TARGET_TYPE_DOCUMENT);
        $redirect->save();

        $response = $this->getRedirectHandler()->checkForRedirect(Request::create('http://example.org/missing-source', 'GET'));

        $this->assertNull($response, 'a redirect to a non-existent target must not produce a response');

        $redirect->delete();
    }

    private function getRedirectHandler(): RedirectHandler
    {
        /** @var RedirectHandler $redirectHandler */
        $redirectHandler = Pimcore::getContainer()->get(RedirectHandler::class);

        return $redirectHandler;
    }
}
