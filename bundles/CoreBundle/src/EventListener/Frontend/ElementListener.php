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

namespace Pimcore\Bundle\CoreBundle\EventListener\Frontend;

use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Helper\ParameterBagHelper;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Service;
use Pimcore\Model\Document;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use Pimcore\Model\Version;
use Pimcore\Security\User\UserLoader;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Handles element setup logic from request.
 *
 * @internal
 */
class ElementListener implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use PimcoreContextAwareTrait;

    public function __construct(
        protected DocumentResolver $documentResolver,
        protected EditmodeResolver $editmodeResolver,
        protected RequestHelper $requestHelper,
        protected UserLoader $userLoader
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 30], // has to be after DocumentFallbackListener
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if ($event->isMainRequest()) {
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
                $this->requestHelper->isFrontendRequestByAdmin($this->requestHelper->getMainRequest());

            $user = null;
            if ($adminRequest) {
                $user = $this->userLoader->getUser();
            }

            if ($document && !$document->isPublished() && !$user) {
                $this->logger->warning(
                    "Denying access to document {$document->getFullPath()} as it is unpublished and there is no user in the session."
                );

                throw new AccessDeniedHttpException(sprintf('Access denied for %s', $document->getFullPath()));
            }

            // editmode, pimcore_preview & pimcore_version
            if ($user) {
                $document = $this->handleAdminUserDocumentParams($request, $document, $user);
                $this->handleObjectParams($request, $user);
            }

            if ($document) {
                // for public versions
                $document = $this->handleVersion($request, $document);

                $this->documentResolver->setDocument($request, $document);
            }
        }
    }

    protected function handleVersion(Request $request, Document $document): Document
    {
        $v = ParameterBagHelper::getInt($request->query, 'v');
        if ($v) {
            if ($version = Version::getById($v)) {
                if ($version->getPublic()) {
                    $this->logger->info('Setting version to {version} for document {document}', [
                        'version' => $version->getId(),
                        'document' => $document->getFullPath(),
                    ]);

                    $document = $version->getData();
                }
            } else {
                $this->logger->notice('Failed to load {version} for document {document}', [
                    'version' => $v,
                    'document' => $document->getFullPath(),
                ]);
            }
        }

        return $document;
    }

    private function handleAdminUserDocumentParams(Request $request, ?Document $document, User $user): ?Document
    {
        if (!$document) {
            return null;
        }

        $isPimcoreStudio = $request->query->getBoolean('pimcore_studio');

        // editmode document
        if ($this->editmodeResolver->isEditmode($request)) {
            $document = $this->handleEditmode($document, $user, $request->getSession(), $isPimcoreStudio);
        }

        // document preview
        if ($request->query->getBoolean('pimcore_studio_preview')) {
            $document = $this->handleDocumentStudioPreview($document, $user);
        } elseif ($request->query->getBoolean('pimcore_preview')) {
            $document = $this->handleDocumentClassicPreview($request, $document);
        }

        // for version preview
        if ($request->query->has('pimcore_version')) {
            $versionId = ParameterBagHelper::getInt($request->query, 'pimcore_version');
            // TODO there was a check with a registry flag here - check if the main request handling is sufficient
            $version = Version::getById($versionId);
            if ($documentVersion = $version?->getData()) {
                $document = $documentVersion;
                $this->logger->debug('Loading version {version} for document {document} from pimcore_version parameter', [
                    'version' => $version->getId(),
                    'document' => $document->getFullPath(),
                ]);
            } else {
                $this->logger->warning('Failed to load {version} for document {document} from pimcore_version parameter', [
                    'version' => $versionId,
                    'document' => $document->getFullPath(),
                ]);

                throw new NotFoundHttpException(
                    sprintf('Failed to load %s for document %s from pimcore_version parameter',
                        $versionId, $document->getFullPath()));
            }
        }

        return $document;
    }

    protected function handleEditmode(
        Document $document,
        User $user,
        SessionInterface $session,
        bool $isPimcoreStudio
    ): Document {
        if (!$isPimcoreStudio) {
            // check if there is the document in the session (for admin classic UI)
            $documentFromSession = Document\Service::getElementFromSession('document', $document->getId(), $session->getId());
            if ($documentFromSession) {
                // if there is a document in the session use it
                $this->logger->debug('Loading editmode document {document} from session', [
                    'document' => $document->getFullPath(),
                ]);

                return $documentFromSession;
            }
        }

        $this->logger->debug('Loading editmode document {document} from latest version', [
            'document' => $document->getFullPath(),
        ]);

        // set the latest available version for editmode if there is no doc in the session
        if ($document instanceof Document\PageSnippet) {
            $latestVersion = $document->getLatestVersion($user->getId());
            if ($latestVersion) {
                $latestDoc = $latestVersion->loadData();

                if ($latestDoc instanceof Document\PageSnippet) {
                    $document = $latestDoc;
                }
            }
        }

        return $document;
    }

    protected function handleObjectParams(Request $request, UserInterface $user): void
    {

        if ($request->query->has('pimcore_studio_preview')) {
            $this->handleObjectStudioPreview(ParameterBagHelper::getInt($request->query, 'pimcore_object_preview'), $user);

            return;
        }

        $this->handleObjectClassicPreview($request, ParameterBagHelper::getInt($request->query, 'pimcore_object_preview'));
    }

    private function handleObjectClassicPreview(Request $request, int $id): void
    {
        $object = Service::getElementFromSession('object', $id, $request->getSession()->getId());
        if (!$object instanceof Concrete) {
            return;
        }

        $this->logObjectLoading(
            $object,
            'Loading object {object} ({objectId}) for classic admin preview from session'
        );

        $this->cacheObject($object);
    }

    private function handleObjectStudioPreview(int $id, UserInterface $user): void
    {
        $object = $this->getLatestVersion($id, $user);
        if (!$object instanceof Concrete) {
            return;
        }

        $this->logObjectLoading(
            $object,
            'Loading object {object} ({objectId}) for studio preview'
        );

        $this->cacheObject($object);
    }

    private function handleDocumentClassicPreview(Request $request, Document $document): Document
    {
        // get document from session
        if ($documentFromSession = Document\Service::getElementFromSession('document', $document->getId(), $request->getSession()->getId())) {
            // if there is a document in the session use it
            $this->logger->debug('Loading preview document {document} from session', [
                'document' => $document->getFullPath(),
            ]);

            return $documentFromSession;
        }

        return $document;
    }

    private function handleDocumentStudioPreview(Document $document, User $user): Document
    {
        $this->logger->debug('Loading preview document {document} from latest version', [
            'document' => $document->getFullPath(),
        ]);

        if ($document instanceof Document\PageSnippet) {
            $latestVersion = $document->getLatestVersion($user->getId());
            if ($latestVersion) {
                $latestDoc = $latestVersion->loadData();

                if ($latestDoc instanceof Document\PageSnippet) {
                    return $latestDoc;
                }
            }
        }

        return $document;
    }

    private function getLatestVersion(int $id, UserInterface $user): ?Concrete
    {
        $dataObject = Service::getElementById('object', $id);

        if (!$dataObject instanceof Concrete) {
            return null;
        }

        $version = $dataObject->getLatestVersion($user->getId());

        if ($version === null || !$version->getData() instanceof Concrete) {
            return $dataObject;
        }

        return $version->getData();
    }

    private function logObjectLoading(Concrete $object, string $message): void
    {
        $this->logger->debug($message, [
            'object' => $object->getFullPath(),
            'objectId' => $object->getId(),
        ]);
    }

    private function cacheObject(Concrete $object): void
    {
        RuntimeCache::set('object_' . $object->getId(), $object);
    }
}
