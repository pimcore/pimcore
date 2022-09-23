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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin\Document;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Bundle\AdminBundle\Controller\Traits\ApplySchedulerDataTrait;
use Pimcore\Bundle\AdminBundle\Controller\Traits\DocumentTreeConfigTrait;
use Pimcore\Controller\KernelControllerEventInterface;
use Pimcore\Controller\Traits\ElementEditLockHelperTrait;
use Pimcore\Event\Admin\ElementAdminStyleEvent;
use Pimcore\Event\AdminEvents;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Document\Targeting\TargetingDocumentInterface;
use Pimcore\Model\Element;
use Pimcore\Model\Property;
use Pimcore\Model\Version;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @internal
 */
abstract class DocumentControllerBase extends AdminController implements KernelControllerEventInterface
{
    use ApplySchedulerDataTrait;
    use DocumentTreeConfigTrait;
    use ElementEditLockHelperTrait;

    const TASK_PUBLISH = 'publish';

    const TASK_UNPUBLISH = 'unpublish';

    const TASK_SAVE = 'save';

    const TASK_VERSION = 'version';

    const TASK_SCHEDULER = 'scheduler';

    const TASK_AUTOSAVE = 'autosave';

    const TASK_DELETE = 'delete';

    /**
     * @param array $data
     * @param Model\Document $document
     * @param Version|null $draftVersion
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    protected function preSendDataActions(array &$data, Model\Document $document, ?Version $draftVersion = null): JsonResponse
    {
        $documentFromDatabase = Model\Document::getById($document->getId(), ['force' => true]);

        $data['versionDate'] = $documentFromDatabase->getModificationDate();
        $data['userPermissions'] = $document->getUserPermissions();
        $data['idPath'] = Element\Service::getIdPath($document);

        $data['php'] = [
            'classes' => array_merge([get_class($document)], array_values(class_parents($document))),
            'interfaces' => array_values(class_implements($document)),
        ];

        $this->addAdminStyle($document, ElementAdminStyleEvent::CONTEXT_EDITOR, $data);

        if ($draftVersion && $documentFromDatabase->getModificationDate() < $draftVersion->getDate()) {
            $data['draft'] = [
                'id' => $draftVersion->getId(),
                'modificationDate' => $draftVersion->getDate(),
                'isAutoSave' => $draftVersion->isAutoSave(),
            ];
        }

        $event = new GenericEvent($this, [
            'data' => $data,
            'document' => $document,
        ]);
        \Pimcore::getEventDispatcher()->dispatch($event, AdminEvents::DOCUMENT_GET_PRE_SEND_DATA);
        $data = $event->getArgument('data');

        if ($document->isAllowed('view')) {
            return $this->adminJson($data);
        }

        throw $this->createAccessDeniedHttpException();
    }

    /**
     * @param Request $request
     * @param Model\Document $document
     */
    protected function addPropertiesToDocument(Request $request, Model\Document $document)
    {
        // properties
        if ($request->get('properties')) {
            $properties = [];
            // assign inherited properties
            foreach ($document->getProperties() as $p) {
                if ($p->isInherited()) {
                    $properties[$p->getName()] = $p;
                }
            }

            $propertiesData = $this->decodeJson($request->get('properties'));

            if (is_array($propertiesData)) {
                foreach ($propertiesData as $propertyName => $propertyData) {
                    $value = $propertyData['data'];

                    try {
                        $property = new Property();
                        $property->setType($propertyData['type']);
                        $property->setName($propertyName);
                        $property->setCtype('document');
                        $property->setDataFromEditmode($value);
                        $property->setInheritable($propertyData['inheritable']);

                        if ($propertyName === 'language') {
                            $property->setInherited($this->getPropertyInheritance($document, $propertyName, $value));
                        }

                        $properties[$propertyName] = $property;
                    } catch (\Exception $e) {
                        Logger::warning("Can't add " . $propertyName . ' to document ' . $document->getRealFullPath());
                    }
                }
            }
            if ($document->isAllowed('properties')) {
                $document->setProperties($properties);
            }
        }

        // force loading of properties
        $document->getProperties();
    }

    /**
     * @param Request $request
     * @param Model\Document $document
     */
    protected function addSettingsToDocument(Request $request, Model\Document $document): void
    {
        // settings
        if ($request->get('settings')) {
            if ($document->isAllowed('settings')) {
                $settings = $this->decodeJson($request->get('settings'));
                $document->setValues($settings);
            }
        }
    }

    /**
     * @param Request $request
     * @param Model\Document $document
     */
    protected function addDataToDocument(Request $request, Model\Document $document): void
    {
        if ($document instanceof Model\Document\PageSnippet) {
            // if a target group variant get's saved, we have to load all other editables first, otherwise they will get deleted
            if ($request->get('appendEditables') || ($document instanceof TargetingDocumentInterface && $document->hasTargetGroupSpecificEditables())) {
                // ensure editable are loaded
                $document->getEditables();
            } else {
                // ensure no editables (e.g. from session, version, ...) are still referenced
                $document->setEditables(null);
            }

            if ($request->get('data')) {
                $data = $this->decodeJson($request->get('data'));
                foreach ($data as $name => $value) {
                    $data = $value['data'] ?? null;
                    $type = $value['type'];
                    $document->setRawEditable($name, $type, $data);
                }
            }
        }
    }

    /**
     * @param Model\Document $document
     * @param array $data
     */
    protected function addTranslationsData(Model\Document $document, array &$data): void
    {
        $service = new Model\Document\Service;
        $translations = $service->getTranslations($document);
        $unlinkTranslations = $service->getTranslations($document, 'unlink');
        $language = $document->getProperty('language');
        unset($translations[$language], $unlinkTranslations[$language]);
        $data['translations'] = $translations;
        $data['unlinkTranslations'] = $unlinkTranslations;
    }

    /**
     * @Route("/save-to-session", name="savetosession", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function saveToSessionAction(Request $request): JsonResponse
    {
        if ($documentId = (int) $request->get('id')) {
            if (!$document = Model\Document\Service::getElementFromSession('document', $documentId)) {
                $document = Model\Document\PageSnippet::getById($documentId);
                if (!$document) {
                    throw $this->createNotFoundException();
                }
                $document = $this->getLatestVersion($document);
            }

            // set dump state to true otherwise the properties will be removed because of the session-serialize
            $document->setInDumpState(true);
            $this->setValuesToDocument($request, $document);

            Model\Document\Service::saveElementToSession($document);
        }

        return $this->adminJson(['success' => true]);
    }

    /**
     * @param Model\Document $doc
     * @param bool $useForSave
     */
    protected function saveToSession(Model\Document $doc, bool $useForSave = false): void
    {
        // save to session
        Model\Document\Service::saveElementToSession($doc);

        if ($useForSave) {
            Model\Document\Service::saveElementToSession($doc, '_useForSave');
        }
    }

    /**
     * @param Model\Document $doc
     *
     * @return Model\Document|null $sessionDocument
     */
    protected function getFromSession(Model\Document $doc): ?Model\Document
    {
        $sessionDocument = null;

        // check if there's a document in session which should be used as data-source
        // see also PageController::clearEditableDataAction() | this is necessary to reset all fields and to get rid of
        // outdated and unused data elements in this document (eg. entries of area-blocks)

        if (($sessionDocument = Model\Document\Service::getElementFromSession('document', $doc->getId())) &&
            (Model\Document\Service::getElementFromSession('document', $doc->getId(), '_useForSave'))) {
            Model\Document\Service::removeElementFromSession('document', $doc->getId(), '_useForSave');
        }

        return $sessionDocument;
    }

    /**
     * @Route("/remove-from-session", name="removefromsession", methods={"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function removeFromSessionAction(Request $request): JsonResponse
    {
        Model\Document\Service::removeElementFromSession('document', $request->get('id'));

        return $this->adminJson(['success' => true]);
    }

    /**
     * @param Model\Document $document
     * @param array $data
     */
    protected function minimizeProperties(Model\Document $document, array &$data): void
    {
        $data['properties'] = Model\Element\Service::minimizePropertiesForEditmode($document->getProperties());
    }

    /**
     * @param Model\Document $document
     * @param string $propertyName
     * @param mixed $propertyValue
     *
     * @return bool
     */
    protected function getPropertyInheritance(Model\Document $document, string $propertyName, mixed $propertyValue): bool
    {
        if ($document->getParent()) {
            return $propertyValue == $document->getParent()->getProperty($propertyName);
        }

        return false;
    }

    /**
     * @template T of Model\Document\PageSnippet
     *
     * @param T $document
     * @param null|Version $draftVersion
     *
     * @return T
     */
    protected function getLatestVersion(Model\Document\PageSnippet $document, ?Version &$draftVersion = null): Model\Document\PageSnippet
    {
        $latestVersion = $document->getLatestVersion($this->getAdminUser()->getId());
        if ($latestVersion) {
            $latestDoc = $latestVersion->loadData();
            if ($latestDoc instanceof Model\Document\PageSnippet) {
                $draftVersion = $latestVersion;

                return $latestDoc;
            }
        }

        return $document;
    }

    /**
     * This is used for pages and snippets to change the master document (which is not saved with the normal save button)
     *
     * @Route("/change-master-document", name="changemasterdocument", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function changeMasterDocumentAction(Request $request): JsonResponse
    {
        $doc = Model\Document\PageSnippet::getById((int) $request->get('id'));
        if ($doc instanceof Model\Document\PageSnippet) {
            $doc->setEditables([]);
            $doc->setContentMasterDocumentId($request->get('contentMasterDocumentPath'), true);
            $doc->saveVersion();
        }

        return $this->adminJson(['success' => true]);
    }

    /**
     * @param ControllerEvent $event
     */
    public function onKernelControllerEvent(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // check permissions
        $this->checkPermission('documents');
    }

    /**
     * @param Request $request
     * @param Model\Document $page
     */
    abstract protected function setValuesToDocument(Request $request, Model\Document $page);

    /**
     * @param string $task
     * @param Model\Document\PageSnippet $page
     *
     */
    protected function handleTask(string $task, Model\Document\PageSnippet $page)
    {
        if ($task === self::TASK_PUBLISH || $task === self::TASK_VERSION) {
            $page->deleteAutoSaveVersions($this->getAdminUser()->getId());
        }
    }

    /**
     * @param Model\Document $document
     *
     * @return bool|JsonResponse
     */
    protected function checkForLock(Model\Document $document): JsonResponse|bool
    {
        // check for lock
        if ($document->isAllowed(self::TASK_SAVE)
            || $document->isAllowed(self::TASK_PUBLISH)
            || $document->isAllowed(self::TASK_UNPUBLISH)
            || $document->isAllowed(self::TASK_DELETE)) {
            if (Element\Editlock::isLocked($document->getId(), 'document')) {
                return $this->getEditLockResponse($document->getId(), 'document');
            }
            Element\Editlock::lock($document->getId(), 'document');
        }

        return true;
    }

    /**
     * @throws Element\ValidationException
     * @throws \Exception
     */
    protected function saveDocument(Model\Document $document, Request $request, bool $latestVersion = false, $task = null): array
    {
        if ($latestVersion && $document instanceof  Model\Document\PageSnippet) {
            $document = $this->getLatestVersion($document);
        }

        //update modification info
        $document->setModificationDate(time());
        $document->setUserModification($this->getAdminUser()->getId());

        $task = strtolower($task ?? $request->get('task'));
        $version = null;
        switch ($task) {
            case $task === self::TASK_PUBLISH && $document->isAllowed($task):
                $this->setValuesToDocument($request, $document);
                $document->setPublished(true);
                $document->save();

                break;
            case $task === self::TASK_UNPUBLISH && $document->isAllowed($task):
                $this->setValuesToDocument($request, $document);
                $document->setPublished(false);
                $document->save();

                break;
            case in_array($task, [self::TASK_SAVE, self::TASK_VERSION, self::TASK_AUTOSAVE])
            && $document->isAllowed(self::TASK_SAVE):
                if ($document instanceof Model\Document\PageSnippet) {
                    $this->setValuesToDocument($request, $document);
                    $version = $document->saveVersion(true, true, null, $task === self::TASK_AUTOSAVE);
                }

                break;
            case $task === self::TASK_SCHEDULER && $document->isAllowed('settings'):
                if ($document instanceof Model\Document\PageSnippet
                    || $document instanceof Model\Document\Hardlink
                    || $document instanceof Model\Document\Link) {
                    $this->applySchedulerDataToElement($request, $document);
                    $document->saveScheduledTasks();
                }

                break;
            default:
                throw $this->createAccessDeniedHttpException();
        }

        if ($document instanceof Model\Document\PageSnippet) {
            $this->handleTask($task, $document);
        }

        return [$task, $document, $version];
    }
}
