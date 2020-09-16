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

use Pimcore\Bundle\AdminBundle\Security\User\UserLoader;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\DataObject\Service;
use Pimcore\Model\Document;
use Pimcore\Model\Staticroute;
use Pimcore\Model\Version;
use Pimcore\Targeting\Document\DocumentTargetingConfigurator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Handles element setup logic from request. Basically this does what the init() method
 * on the ZF frontend controller did.
 */
class ElementListener implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use PimcoreContextAwareTrait;

    const FORCE_ALLOW_PROCESSING_UNPUBLISHED_ELEMENTS = '_force_allow_processing_unpublished_elements';

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
     * @var DocumentTargetingConfigurator
     */
    private $targetingConfigurator;

    public function __construct(
        DocumentResolver $documentResolver,
        EditmodeResolver $editmodeResolver,
        RequestHelper $requestHelper,
        UserLoader $userLoader,
        DocumentTargetingConfigurator $targetingConfigurator
    ) {
        $this->documentResolver = $documentResolver;
        $this->editmodeResolver = $editmodeResolver;
        $this->requestHelper = $requestHelper;
        $this->userLoader = $userLoader;
        $this->targetingConfigurator = $targetingConfigurator;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 3], // has to be after DocumentFallbackListener
        ];
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        if ($event->isMasterRequest()) {
            $request = $event->getRequest();
            if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
                return;
            }

            if ($request->attributes->get('_route') === 'fos_js_routing_js') {
                return;
            }

            $document = $this->documentResolver->getDocument($request);
            $adminRequest =
                $this->requestHelper->isFrontendRequestByAdmin($request) ||
                $this->requestHelper->isFrontendRequestByAdmin($this->requestHelper->getMasterRequest());

            $user = null;
            if ($adminRequest) {
                $user = $this->userLoader->getUser();
            }

            if ($document && !$document->isPublished() && !$user && !$request->attributes->get(self::FORCE_ALLOW_PROCESSING_UNPUBLISHED_ELEMENTS)) {
                $this->logger->warning('Denying access to document {document} as it is unpublished and there is no user in the session.', [
                    $document->getFullPath(),
                ]);

                throw new AccessDeniedHttpException(sprintf('Access denied for %s', $document->getFullPath()));
            }

            // editmode, pimcore_preview & pimcore_version
            if ($user) {
                $document = $this->handleAdminUserDocumentParams($request, $document);
                $this->handleObjectParams($request);
            }

            if ($document) {
                // for public versions
                $document = $this->handleVersion($request, $document);

                // apply target group configuration
                $this->applyTargetGroups($request, $document);

                $this->documentResolver->setDocument($request, $document);
            }
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
            if ($version = Version::getById($request->get('v'))) {
                if ($version->getPublic()) {
                    $this->logger->info('Setting version to {version} for document {document}', [
                        'version' => $version->getId(),
                        'document' => $document->getFullPath(),
                    ]);

                    $document = $version->getData();
                }
            } else {
                $this->logger->notice('Failed to load {version} for document {document}', [
                    'version' => $request->get('v'),
                    'document' => $document->getFullPath(),
                ]);
            }
        }

        return $document;
    }

    protected function applyTargetGroups(Request $request, Document $document)
    {
        if (!$document instanceof Document\Targeting\TargetingDocumentInterface || null !== Staticroute::getCurrentRoute()) {
            return;
        }

        // reset because of preview and editmode (saved in session)
        $document->setUseTargetGroup(null);

        $this->targetingConfigurator->configureTargetGroup($document);

        if ($document->getUseTargetGroup()) {
            $this->logger->info('Setting target group to {targetGroup} for document {document}', [
                'targetGroup' => $document->getUseTargetGroup(),
                'document' => $document->getFullPath(),
            ]);
        }
    }

    /**
     * @param Request $request
     * @param Document|null $document
     *
     * @return Document|null
     */
    protected function handleAdminUserDocumentParams(Request $request, ?Document $document)
    {
        if (!$document) {
            return null;
        }

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

            if ($documentFromSession = Document\Service::getElementFromSession('document', $document->getId())) {
                // if there is a document in the session use it
                $this->logger->debug('Loading preview document {document} from session', [
                    'document' => $document->getFullPath(),
                ]);
                $document = $documentFromSession;
            }
        }

        // for version preview
        if ($request->get('pimcore_version')) {
            // TODO there was a check with a registry flag here - check if the master request handling is sufficient
            if ($version = Version::getById($request->get('pimcore_version'))) {
                $document = $version->getData();

                $this->logger->debug('Loading version {version} for document {document} from pimcore_version parameter', [
                    'version' => $version->getId(),
                    'document' => $document->getFullPath(),
                ]);
            } else {
                $this->logger->warning('Failed to load {version} for document {document} from pimcore_version parameter', [
                    'version' => $request->get('pimcore_version'),
                    'document' => $document->getFullPath(),
                ]);

                throw new NotFoundHttpException(
                    sprintf('Failed to load %s for document %s from pimcore_version parameter',
                        $request->get('pimcore_version'), $document->getFullPath()));
            }
        }

        return $document;
    }

    /**
     * @param Document $document
     *
     * @return mixed|Document|Document\PageSnippet
     */
    protected function handleEditmode(Document $document)
    {
        // check if there is the document in the session
        if ($documentFromSession = Document\Service::getElementFromSession('document', $document->getId())) {
            // if there is a document in the session use it
            $this->logger->debug('Loading editmode document {document} from session', [
                'document' => $document->getFullPath(),
            ]);
            $document = $documentFromSession;
        } else {
            $this->logger->debug('Loading editmode document {document} from latest version', [
                'document' => $document->getFullPath(),
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
        if ($objectId = $request->get('pimcore_object_preview')) {
            if ($object = Service::getElementFromSession('object', $objectId)) {
                $this->logger->debug('Loading object {object} ({objectId}) from session', [
                    'object' => $object->getFullPath(),
                    'objectId' => $object->getId(),
                ]);

                // TODO remove \Pimcore\Cache\Runtime
                // add the object to the registry so every call to DataObject::getById() will return this object instead of the real one
                \Pimcore\Cache\Runtime::set('object_' . $object->getId(), $object);
            }
        }
    }
}
