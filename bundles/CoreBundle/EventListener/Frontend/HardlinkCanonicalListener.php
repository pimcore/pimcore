<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\CoreBundle\EventListener\Frontend;

use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Hardlink\Wrapper\WrapperInterface;
use Pimcore\Model\Site;
use Pimcore\Model\Staticroute;
use Pimcore\Tool\Frontend;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Sets canonical headers for hardlink documents
 */
class HardlinkCanonicalListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    /**
     * @var DocumentResolver
     */
    protected $documentResolver;

    /**
     * @param DocumentResolver $documentResolver
     */
    public function __construct(DocumentResolver $documentResolver)
    {
        $this->documentResolver = $documentResolver;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!$event->isMasterRequest()) {
            return;
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        $document = $this->documentResolver->getDocument($request);
        if (!$document) {
            return;
        }

        if ($document instanceof WrapperInterface && !Staticroute::getCurrentRoute()) {
            $this->handleHardlink($request, $event->getResponse(), $document);
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param Document $document
     */
    protected function handleHardlink(Request $request, Response $response, Document $document)
    {
        $canonical = null;

        // get the canonical (source) document
        $hardlinkCanonicalSourceDocument = Document::getById($document->getId());

        if (Frontend::isDocumentInCurrentSite($hardlinkCanonicalSourceDocument)) {
            $canonical = $request->getSchemeAndHttpHost() . $hardlinkCanonicalSourceDocument->getFullPath();
        } elseif (Site::isSiteRequest()) {
            $sourceSite = Frontend::getSiteForDocument($hardlinkCanonicalSourceDocument);
            if ($sourceSite) {
                if ($sourceSite->getMainDomain()) {
                    $sourceSiteRelPath = preg_replace('@^' . preg_quote($sourceSite->getRootPath(), '@') . '@', '', $hardlinkCanonicalSourceDocument->getRealFullPath());
                    $canonical = $request->getScheme() . '://' . $sourceSite->getMainDomain() . $sourceSiteRelPath;
                }
            }
        }

        if ($canonical) {
            $response->headers->set('Link', '<' . $canonical . '>; rel="canonical"', false);
        }
    }
}
