<?php

namespace Pimcore\Bundle\PimcoreBundle\EventListener\Frontend;

use Pimcore\Bundle\PimcoreAdminBundle\Security\User\UserLoader;
use Pimcore\Bundle\PimcoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\PimcoreBundle\Service\Request\DocumentResolver;
use Pimcore\Bundle\PimcoreBundle\Service\Request\EditmodeResolver;
use Pimcore\Bundle\PimcoreBundle\Service\Request\PimcoreContextResolver;
use Pimcore\Bundle\PimcoreBundle\Service\Request\PimcoreContextResolverAwareInterface;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\Asset\Dao;
use Pimcore\Model\Document;
use Pimcore\Model\Object\Concrete;
use Pimcore\Model\Version;
use Pimcore\Tool\Session;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Handles element setup logic from request. Basically this does what the init() method
 * on the ZF frontend controller did.
 */
class ElementListener extends AbstractFrontendListener implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var DocumentResolver
     */
    protected $documentResolver;

    /**
     * @var EditmodeResolver
     */
    protected $editmodeResolver;

    /**
     * @var RequestHelper
     */
    protected $requestHelper;

    /**
     * @var UserLoader
     */
    protected $userLoader;

    /**
     * @param DocumentResolver $documentResolver
     * @param EditmodeResolver $editmodeResolver
     * @param RequestHelper $requestHelper
     * @param UserLoader $userLoader
     */
    public function __construct(
        DocumentResolver $documentResolver,
        EditmodeResolver $editmodeResolver,
        RequestHelper $requestHelper,
        UserLoader $userLoader
    )
    {
        $this->documentResolver = $documentResolver;
        $this->editmodeResolver = $editmodeResolver;
        $this->requestHelper    = $requestHelper;
        $this->userLoader       = $userLoader;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 3], // has to be right after DocumentFallbackListener
        ];
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        $document = $this->documentResolver->getDocument($request);
        if (!$document) {
            return;
        }

        $adminRequest = $this->requestHelper->isFrontendRequestByAdmin($this->requestHelper->getMasterRequest());

        $user = null;
        if ($adminRequest) {
            $user = $this->userLoader->getUser();
        }

        if (!$document->isPublished() && !$user) {
            $this->logger->warning('Denying access to document {document} as it is unpublished and there is no user in the session.', [
                $document->getFullPath()
            ]);

            throw new AccessDeniedHttpException(sprintf('Access denied for %s', $document->getFullPath()));
        }

        if ($event->isMasterRequest()) {
            // editmode, pimcore_preview & pimcore_version
            if ($user) {
                $document = $this->handleAdminUserDocumentParams($request, $document);

                $this->handleObjectParams($request);
            }

            // for public versions
            $document = $this->handleVersion($request, $document);

            // check for persona
            $document = $this->handlePersona($request, $document);

            $this->documentResolver->setDocument($request, $document);
        }
    }

    /**
     * @param Request $request
     * @param Document $document
     *
     * @return Document
     */
    protected function handleVersion(Request $request, Document $document)
    {
        if ($request->get('v')) {
            try {
                $version = Version::getById($request->get('v'));
                if ($version->getPublic()) {
                    $this->logger->info('Setting version to {version} for document {document}', [
                        'version'  => $version->getId(),
                        'document' => $document->getFullPath()
                    ]);

                    $document = $version->getData();
                }
            } catch (\Exception $e) {
                $this->logger->notice('Failed to load {version} for document {document}', [
                    'version'  => $request->get('v'),
                    'document' => $document->getFullPath()
                ]);
            }
        }

        return $document;
    }

    /**
     * @param Request $request
     * @param Document $document
     *
     * @return Document
     */
    protected function handlePersona(Request $request, Document $document)
    {
        if ($document instanceof Document\Page) {
            // reset because of preview and editmode (saved in session)
            $document->setUsePersona(null);

            if ($request->get('_ptp')) {
                $this->logger->info('Setting persona to {persona} for document {document}', [
                    'persona'  => $request->get('_ptp'),
                    'document' => $document->getFullPath()
                ]);

                $document->setUsePersona($request->get('_ptp'));
            }
        }

        return $document;
    }

    /**
     * @param Request $request
     * @param Document|Dao $document
     *
     * @return Document
     */
    protected function handleAdminUserDocumentParams(Request $request, Document $document)
    {
        // editmode document
        if ($this->editmodeResolver->isEditmode($request)) {
            $document = $this->handleEditmode($document);
        }

        // document preview
        if ($request->get('pimcore_preview')) {
            // get document from session

            // TODO originally, this was the following call. What was in this->getParam('document') and
            // why was it an object?
            // $docKey = "document_" . $this->getParam("document")->getId();

            $docKey     = 'document_' . $document->getId();
            $docSession = Session::getReadOnly('pimcore_documents');

            if ($docSession->has($docKey)) {
                $this->logger->debug('Loading preview document {document} from session', [
                    'document' => $document->getFullPath()
                ]);

                // if there is a document in the session use it
                $document = $docSession->get($docKey);
            }
        }

        // for version preview
        if ($request->get('pimcore_version')) {
            // TODO there was a check with a registry flag here - check if the master request handling is sufficient
            try {
                $version  = Version::getById($request->get('pimcore_version'));
                $document = $version->getData();

                $this->logger->debug('Loading version {version} for document {document} from pimcore_version parameter', [
                    'version'  => $version->getId(),
                    'document' => $document->getFullPath()
                ]);
            } catch (\Exception $e) {
                $this->logger->warning('Failed to load {version} for document {document} from pimcore_version parameter', [
                    'version'  => $request->get('pimcore_version'),
                    'document' => $document->getFullPath()
                ]);

                // TODO throw a less generic excdption in getById() and only catch that one here
                throw new NotFoundHttpException($e->getMessage());
            }
        }

        return $document;
    }

    /**
     * @param Document|Dao $document
     * @return mixed|Document|Document\PageSnippet
     */
    protected function handleEditmode(Document $document)
    {
        // check if there is the document in the session
        $docKey     = 'document_' . $document->getId();
        $docSession = Session::getReadOnly('pimcore_documents');

        if ($docSession->has($docKey)) {
            $this->logger->debug('Loading editmode document {document} from session', [
                'document' => $document->getFullPath()
            ]);

            // if there is a document in the session use it
            $document = $docSession->get($docKey);
        } else {
            $this->logger->debug('Loading editmode document {document} from latest version', [
                'document' => $document->getFullPath()
            ]);

            // set the latest available version for editmode if there is no doc in the session
            $latestVersion = $document->getLatestVersion();
            if ($latestVersion) {
                $latestDoc = $latestVersion->loadData();

                if ($latestDoc instanceof Document\PageSnippet) {
                    $document = $latestDoc;
                }
            }
        }

        return $document;
    }

    /**
     * @param Request $request
     */
    protected function handleObjectParams(Request $request)
    {
        // object preview
        if ($request->get('pimcore_object_preview')) {
            $key = 'object_' . $request->get('pimcore_object_preview');

            $session = Session::getReadOnly('pimcore_objects');
            if ($session->has($key)) {
                /** @var Object|Concrete $object */
                $object = $session->get($key);

                $this->logger->debug('Loading object {object} ({objectId}) from session', [
                    'object'   => $object->getFullPath(),
                    'objectId' => $object->getId()
                ]);

                // TODO remove \Pimcore\Cache\Runtime
                // add the object to the registry so every call to Object::getById() will return this object instead of the real one
                \Pimcore\Cache\Runtime::set("object_" . $object->getId(), $object);
            }
        }
    }
}
