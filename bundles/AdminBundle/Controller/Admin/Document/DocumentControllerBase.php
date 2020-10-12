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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin\Document;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Bundle\AdminBundle\Controller\Traits\ApplySchedulerDataTrait;
use Pimcore\Bundle\AdminBundle\Controller\Traits\DocumentTreeConfigTrait;
use Pimcore\Controller\EventedControllerInterface;
use Pimcore\Event\Admin\ElementAdminStyleEvent;
use Pimcore\Event\AdminEvents;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Document\Targeting\TargetingDocumentInterface;
use Pimcore\Model\Element;
use Pimcore\Model\Property;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

abstract class DocumentControllerBase extends AdminController implements EventedControllerInterface
{
    use ApplySchedulerDataTrait;
    use DocumentTreeConfigTrait;

    protected function preSendDataActions(&$data, Model\Document $document)
    {
        $documentFromDatabase = Model\Document::getById($document->getId(), true);

        $data['versionDate'] = $documentFromDatabase->getModificationDate();
        $data['userPermissions'] = $document->getUserPermissions();
        $data['idPath'] = Element\Service::getIdPath($document);

        $data['php'] = [
            'classes' => array_merge([get_class($document)], array_values(class_parents($document))),
            'interfaces' => array_values(class_implements($document)),
        ];

        $this->addAdminStyle($document, ElementAdminStyleEvent::CONTEXT_EDITOR, $data);

        $event = new GenericEvent($this, [
            'data' => $data,
            'document' => $document,
        ]);
        \Pimcore::getEventDispatcher()->dispatch(AdminEvents::DOCUMENT_GET_PRE_SEND_DATA, $event);
        $data = $event->getArgument('data');
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

                        if ($propertyName == 'language') {
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
    protected function addSettingsToDocument(Request $request, Model\Document $document)
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
     * @param Model\Document\PageSnippet $document
     */
    protected function addDataToDocument(Request $request, Model\Document\PageSnippet $document)
    {
        // if a target group variant get's saved, we have to load all other editables first, otherwise they will get deleted
        if ($request->get('appendEditables') || ($document instanceof TargetingDocumentInterface && $document->hasTargetGroupSpecificEditables())) {
            $document->getEditables();
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

    /**
     * @param Model\Document $document
     * @param array $data
     */
    protected function addTranslationsData(Model\Document $document, array &$data)
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
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function saveToSessionAction(Request $request)
    {
        if ($request->get('id')) {
            $documentId = $request->get('id');
            if (!$document = Model\Document\Service::getElementFromSession('document', $documentId)) {
                $document = Model\Document::getById($request->get('id'));
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
    protected function saveToSession($doc, $useForSave = false)
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
    protected function getFromSession($doc)
    {
        $sessionDocument = null;

        if ($doc instanceof Model\Document) {
            // check if there's a document in session which should be used as data-source
            // see also PageController::clearEditableDataAction() | this is necessary to reset all fields and to get rid of
            // outdated and unused data elements in this document (eg. entries of area-blocks)

            if (($sessionDocument = Model\Document\Service::getElementFromSession('document', $doc->getId())) &&
                ($documentForSave = Model\Document\Service::getElementFromSession('document', $doc->getId(), '_useForSave'))) {
                Model\Document\Service::removeElementFromSession('document', $doc->getId(), '_useForSave');
            }
        }

        return $sessionDocument;
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function removeFromSessionAction(Request $request)
    {
        Model\Document\Service::removeElementFromSession('document', $request->get('id'));

        return $this->adminJson(['success' => true]);
    }

    /**
     * @param Model\Document $document
     * @param array $data
     */
    protected function minimizeProperties($document, array &$data)
    {
        $data['properties'] = Model\Element\Service::minimizePropertiesForEditmode($document->getProperties());
    }

    /**
     * @param Model\Document $document
     * @param string $propertyName
     * @param string $propertyValue
     *
     * @return bool
     */
    protected function getPropertyInheritance(Model\Document $document, $propertyName, $propertyValue)
    {
        if ($document->getParent()) {
            return $propertyValue == $document->getParent()->getProperty($propertyName);
        }

        return false;
    }

    /**
     * @param Model\Document\PageSnippet $document
     * @param bool $isLatestVersion
     *
     * @return Model\Document\PageSnippet
     */
    protected function getLatestVersion(Model\Document\PageSnippet $document, &$isLatestVersion = true)
    {
        $latestVersion = $document->getLatestVersion();
        if ($latestVersion) {
            $latestDoc = $latestVersion->loadData();
            if ($latestDoc instanceof Model\Document\PageSnippet) {
                $isLatestVersion = false;

                return $latestDoc;
            }
        }

        return $document;
    }

    /**
     * This is used for pages and snippets to change the master document (which is not saved with the normal save button)
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function changeMasterDocumentAction(Request $request)
    {
        $doc = Model\Document::getById($request->get('id'));
        if ($doc instanceof Model\Document\PageSnippet) {
            $doc->setEditables([]);
            $doc->setContentMasterDocumentId($request->get('contentMasterDocumentPath'));
            $doc->saveVersion();
        }

        return $this->adminJson(['success' => true]);
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $isMasterRequest = $event->isMasterRequest();
        if (!$isMasterRequest) {
            return;
        }

        // check permissions
        $this->checkPermission('documents');
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }

    /**
     * @param Request $request
     * @param Model\Document $page
     */
    abstract protected function setValuesToDocument(Request $request, Model\Document $page);
}
