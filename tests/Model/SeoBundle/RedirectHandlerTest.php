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
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\ElementType;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Symfony\Component\HttpFoundation\Request;

class RedirectHandlerTest extends TestCase
{
    /**
     * @var array<ElementInterface|Redirect>
     */
    private array $fixtures = [];

    /**
     * Fixtures are removed even when a test fails: TestHelper::cleanUp() does not touch the
     * redirects table, so a leaked row would match the source of a later test.
     */
    protected function tearDown(): void
    {
        foreach (array_reverse($this->fixtures) as $fixture) {
            $fixture->delete();
        }

        $this->fixtures = [];

        parent::tearDown();
    }

    private function register(ElementInterface $element): ElementInterface
    {
        $this->fixtures[] = $element;

        return $element;
    }

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
        $this->fixtures[] = $redirect;

        /** @var RedirectHandler $redirectHandler */
        $redirectHandler = Pimcore::getContainer()->get(RedirectHandler::class);

        $request = Request::create('http://example.org/source', 'GET');
        $response = $redirectHandler->checkForRedirect($request);

        $this->assertTrue($response->isRedirect(), 'Redirect because redirect source and request path match');
        $this->assertEquals('/target', $response->headers->get('Location'), 'Redirect target should be /target');

        $request = Request::create('http://example.org/other_source', 'GET');
        $response = $redirectHandler->checkForRedirect($request);
        $this->assertNull($response, 'Redirected although path did not match');
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
        $this->fixtures[] = $redirect;

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

        $site->delete();
        $otherSite->delete();
    }

    public function testRedirectToDocumentTarget(): void
    {
        $document = $this->register(TestHelper::createEmptyDocumentPage());

        $redirect = new Redirect();
        $redirect->setType(Redirect::TYPE_PATH);
        $redirect->setSource('/document-source');
        $redirect->setTarget((string) $document->getId());
        $redirect->setTargetType(ElementType::Document);
        $redirect->save();
        $this->fixtures[] = $redirect;

        $response = $this->getRedirectHandler()->checkForRedirect(Request::create('http://example.org/document-source', 'GET'));

        $this->assertNotNull($response);
        $this->assertTrue($response->isRedirect());
        $this->assertSame($document->getFullPath(), $response->headers->get('Location'));
    }

    public function testRedirectToLegacyNumericDocumentTargetWithoutTargetType(): void
    {
        // legacy rows (and auto-created redirects) store a numeric document ID without a target type;
        // they must still resolve to the document, see #18293 review feedback
        $document = $this->register(TestHelper::createEmptyDocumentPage());

        $redirect = new Redirect();
        $redirect->setType(Redirect::TYPE_PATH);
        $redirect->setSource('/legacy-source');
        $redirect->setTarget((string) $document->getId());
        $redirect->save();
        $this->fixtures[] = $redirect;

        $response = $this->getRedirectHandler()->checkForRedirect(Request::create('http://example.org/legacy-source', 'GET'));

        $this->assertNotNull($response, 'legacy numeric document target must still resolve');
        $this->assertTrue($response->isRedirect());
        $this->assertSame($document->getFullPath(), $response->headers->get('Location'));
    }

    public function testRedirectToAssetTarget(): void
    {
        $asset = $this->register(TestHelper::createImageAsset());

        $redirect = new Redirect();
        $redirect->setType(Redirect::TYPE_PATH);
        $redirect->setSource('/asset-source');
        $redirect->setTarget((string) $asset->getId());
        $redirect->setTargetType(ElementType::Asset);
        $redirect->save();
        $this->fixtures[] = $redirect;

        $response = $this->getRedirectHandler()->checkForRedirect(Request::create('http://example.org/asset-source', 'GET'));

        $this->assertNotNull($response);
        $this->assertTrue($response->isRedirect());
        $this->assertSame($asset->getFullPath(), $response->headers->get('Location'));
    }

    public function testMissingTargetDoesNotRedirect(): void
    {
        $redirect = new Redirect();
        $redirect->setType(Redirect::TYPE_PATH);
        $redirect->setSource('/missing-source');
        $redirect->setTarget('999999999');
        $redirect->setTargetType(ElementType::Document);
        $redirect->save();
        $this->fixtures[] = $redirect;

        $response = $this->getRedirectHandler()->checkForRedirect(Request::create('http://example.org/missing-source', 'GET'));

        $this->assertNull($response, 'a redirect to a non-existent target must not produce a response');
    }

    private function getRedirectHandler(): RedirectHandler
    {
        /** @var RedirectHandler $redirectHandler */
        $redirectHandler = Pimcore::getContainer()->get(RedirectHandler::class);

        return $redirectHandler;
    }
}
