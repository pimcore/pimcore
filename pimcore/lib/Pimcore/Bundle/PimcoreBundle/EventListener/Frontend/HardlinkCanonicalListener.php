<?php

namespace Pimcore\Bundle\PimcoreBundle\EventListener\Frontend;

use Pimcore\Bundle\PimcoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\PimcoreBundle\Service\Request\DocumentResolver;
use Pimcore\Bundle\PimcoreBundle\Service\Request\PimcoreContextResolver;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Hardlink\Wrapper\WrapperInterface;
use Pimcore\Model\Site;
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
            KernelEvents::RESPONSE => 'onKernelResponse'
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

        if ($document instanceof WrapperInterface) {
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
                    $sourceSiteRelPath = preg_replace("@^" . preg_quote($sourceSite->getRootPath(), "@") . "@", "", $hardlinkCanonicalSourceDocument->getRealFullPath());
                    $canonical         = $request->getScheme() . "://" . $sourceSite->getMainDomain() . $sourceSiteRelPath;
                }
            }
        }

        if ($canonical) {
            $response->headers->set('Link', '<' . $canonical . '>; rel="canonical"', false);
        }
    }
}
