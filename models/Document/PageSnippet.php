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
 * @category   Pimcore
 * @package    Document
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document;

use Pimcore\Document\Editable\EditableUsageResolver;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\DocumentEvent;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Editable\Loader\EditableLoaderInterface;

/**
 * @method \Pimcore\Model\Document\PageSnippet\Dao getDao()
 * @method \Pimcore\Model\Version getLatestVersion()
 */
abstract class PageSnippet extends Model\Document
{
    use Document\Traits\ScheduledTasksTrait;
    /**
     * @var string
     */
    protected $module;

    /**
     * @var string
     */
    protected $controller = 'default';

    /**
     * @var string
     */
    protected $action = 'default';

    /**
     * @var string
     */
    protected $template;

    /**
     * Contains all content-elements of the document
     *
     * @var array
     *
     * @deprecated since v6.7 and will be removed in 7. Use getter/setter methods or $this->editables
     */
    protected $elements = null;

    /**
     * Contains all content-editables of the document
     *
     * @var array|null
     *
     */
    protected $editables = null;

    /**
     * Contains all versions of the document
     *
     * @var array
     */
    protected $versions = null;

    /**
     * @var null|int
     */
    protected $contentMasterDocumentId;

    /**
     * @internal
     *
     * @var bool
     */
    protected $supportsContentMaster = true;

    /**
     * @var null|bool
     */
    protected $missingRequiredEditable = null;

    /**
     * @var array
     *
     * @deprecated since v6.7 and will be removed in 7. Use getter/setter methods or $this->inheritedEditables
     */
    protected $inheritedElements = [];

    /**
     * @var array
     */
    protected $inheritedEditables = [];

    public function __construct()
    {
        $this->elements = & $this->editables;
        $this->inheritedElements = & $this->inheritedEditables;
    }

    /**
     * @param array $params additional parameters (e.g. "versionNote" for the version note)
     *
     * @throws \Exception
     */
    protected function update($params = [])
    {

        // update elements
        $this->getEditables();
        $this->getDao()->deleteAllEditables();

        if (is_array($this->getEditables()) and count($this->getEditables()) > 0) {
            foreach ($this->getEditables() as $name => $editable) {
                if (!$editable->getInherited()) {
                    $editable->setDao(null);
                    $editable->setDocumentId($this->getId());
                    $editable->save();
                }
            }
        }

        // scheduled tasks are saved in $this->saveVersion();

        // load data which must be requested
        $this->getProperties();
        $this->getEditables();

        $this->checkMissingRequiredEditable();
        if ($this->getMissingRequiredEditable() && $this->getPublished()) {
            throw new \Exception('Prevented publishing document - missing values for required editables');
        }

        // update this
        parent::update($params);

        // save version if needed
        $this->saveVersion(false, false, isset($params['versionNote']) ? $params['versionNote'] : null);
    }

    /**
     * @param bool $setModificationDate
     * @param bool $saveOnlyVersion
     * @param string $versionNote
     *
     * @return null|Model\Version
     *
     * @throws \Exception
     */
    public function saveVersion($setModificationDate = true, $saveOnlyVersion = true, $versionNote = null)
    {
        try {
            // hook should be also called if "save only new version" is selected
            if ($saveOnlyVersion) {
                \Pimcore::getEventDispatcher()->dispatch(DocumentEvents::PRE_UPDATE, new DocumentEvent($this, [
                    'saveVersionOnly' => true,
                ]));
            }

            // set date
            if ($setModificationDate) {
                $this->setModificationDate(time());
            }

            // scheduled tasks are saved always, they are not versioned!
            $this->saveScheduledTasks();

            // create version
            $version = null;

            // only create a new version if there is at least 1 allowed
            // or if saveVersion() was called directly (it's a newer version of the object)
            $documentsConfig = \Pimcore\Config::getSystemConfiguration('documents');
            if (!empty($documentsConfig['versions']['steps'])
                || !empty($documentsConfig['versions']['days'])
                || $setModificationDate) {
                $saveStackTrace = !($documentsConfig['versions']['disable_stack_trace'] ?? false);
                $version = $this->doSaveVersion($versionNote, $saveOnlyVersion, $saveStackTrace);
            }

            // hook should be also called if "save only new version" is selected
            if ($saveOnlyVersion) {
                \Pimcore::getEventDispatcher()->dispatch(DocumentEvents::POST_UPDATE, new DocumentEvent($this, [
                    'saveVersionOnly' => true,
                ]));
            }

            return $version;
        } catch (\Exception $e) {
            \Pimcore::getEventDispatcher()->dispatch(DocumentEvents::POST_UPDATE_FAILURE, new DocumentEvent($this, [
                'saveVersionOnly' => true,
                'exception' => $e,
            ]));

            throw $e;
        }
    }

    /**
     * @inheritdoc
     */
    protected function doDelete()
    {
        $versions = $this->getVersions();
        foreach ($versions as $version) {
            $version->delete();
        }

        // remove all tasks
        $this->getDao()->deleteAllTasks();

        parent::doDelete();
    }

    /**
     * Resolves dependencies and create tags for caching out of them
     *
     * @param array $tags
     *
     * @return array
     */
    public function getCacheTags($tags = [])
    {
        $tags = is_array($tags) ? $tags : [];

        $tags = parent::getCacheTags($tags);

        foreach ($this->getEditables() as $editable) {
            $tags = $editable->getCacheTags($this, $tags);
        }

        return $tags;
    }

    /**
     * @see Document::resolveDependencies
     *
     * @return array
     */
    public function resolveDependencies()
    {
        $dependencies = parent::resolveDependencies();

        foreach ($this->getEditables() as $editable) {
            $dependencies = array_merge($dependencies, $editable->resolveDependencies());
        }

        if ($this->getContentMasterDocument() instanceof Document) {
            $key = 'document_' . $this->getContentMasterDocument()->getId();
            $dependencies[$key] = [
                'id' => $this->getContentMasterDocument()->getId(),
                'type' => 'document',
            ];
        }

        return $dependencies;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        if (empty($this->action)) {
            return 'default';
        }

        return $this->action;
    }

    /**
     * @return string
     */
    public function getController()
    {
        if (empty($this->controller)) {
            return 'default';
        }

        return $this->controller;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param string $action
     *
     * @return $this
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @param string $controller
     *
     * @return $this
     */
    public function setController($controller)
    {
        $this->controller = $controller;

        return $this;
    }

    /**
     * @param string $template
     *
     * @return $this
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @param string $module
     *
     * @return $this
     */
    public function setModule($module)
    {
        $this->module = $module;

        return $this;
    }

    /**
     * @return string
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Set raw data of an element (eg. for editmode)
     *
     * @param string $name
     * @param string $type
     * @param mixed $data
     *
     * @return $this
     *
     * @deprecated since v6.7 and will be removed in 7. Use setRawEditable() instead.
     */
    public function setRawElement($name, $type, $data)
    {
        return $this->setRawEditable($name, $type, $data);
    }

    /**
     * Set raw data of an editable (eg. for editmode)
     *
     * @param string $name
     * @param string $type
     * @param mixed $data
     *
     * @return $this
     */
    public function setRawEditable(string $name, string $type, $data)
    {
        try {
            if ($type) {
                /** @var EditableLoaderInterface $loader */
                $loader = \Pimcore::getContainer()->get(Document\Editable\Loader\EditableLoader::class);
                $editable = $loader->build($type);

                $this->editables = $this->editables ?? [];
                $this->editables[$name] = $editable;
                $this->editables[$name]->setDataFromEditmode($data);
                $this->editables[$name]->setName($name);
                $this->editables[$name]->setDocument($this);
            }
        } catch (\Exception $e) {
            Logger::warning("can't set element " . $name . ' with the type ' . $type . ' to the document: ' . $this->getRealFullPath());
        }

        return $this;
    }

    /**
     * Set an element with the given key/name
     *
     * @param string $name
     * @param Editable $data
     *
     * @return $this
     *
     * @deprecated since v6.7 and will be removed in 7. Use setEditable() instead.
     */
    public function setElement($name, $data)
    {
        return $this->setEditable($name, $data);
    }

    /**
     * Set an element with the given key/name
     *
     * @param string $name
     * @param Editable $data
     *
     * @return $this
     */
    public function setEditable(string $name, Editable $data)
    {
        $this->getEditables();
        $this->editables[$name] = $data;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return $this
     *
     * @deprecated since v6.7 and will be removed in 7. Use removeEditable() instead.
     */
    public function removeElement($name)
    {
        return $this->removeEditable($name);
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function removeEditable(string $name)
    {
        $this->getEditables();
        if (isset($this->editables[$name])) {
            unset($this->editables[$name]);
        }

        return $this;
    }

    /**
     * Get an element with the given key/name
     *
     * @param string $name
     *
     * @return Editable|null
     *
     * @deprecated since v6.7 and will be removed in 7. Use getEditable() instead.
     */
    public function getElement($name)
    {
        return $this->getEditable($name);
    }

    /**
     * Get an editable with the given key/name
     *
     * @param string $name
     *
     * @return Editable|null
     */
    public function getEditable(string $name)
    {
        $editables = $this->getEditables();
        if (isset($this->editables[$name])) {
            return $editables[$name];
        }

        if (array_key_exists($name, $this->inheritedEditables)) {
            return $this->inheritedEditables[$name];
        }

        // check for content master document (inherit data)
        if ($contentMasterDocument = $this->getContentMasterDocument()) {
            if ($contentMasterDocument instanceof self) {
                $inheritedEditable = $contentMasterDocument->getEditable($name);
                if ($inheritedEditable) {
                    $inheritedEditable = clone $inheritedEditable;
                    $inheritedEditable->setInherited(true);
                    $this->inheritedEditables[$name] = $inheritedEditable;

                    return $inheritedEditable;
                }
            }
        }

        return null;
    }

    /**
     * @param int|null $contentMasterDocumentId
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setContentMasterDocumentId($contentMasterDocumentId)
    {
        // this is that the path is automatically converted to ID => when setting directly from admin UI
        if (!is_numeric($contentMasterDocumentId) && !empty($contentMasterDocumentId)) {
            $contentMasterDocument = Document::getByPath($contentMasterDocumentId);
            if ($contentMasterDocument instanceof self) {
                $contentMasterDocumentId = $contentMasterDocument->getId();
            }
        }

        if (empty($contentMasterDocumentId)) {
            $contentMasterDocument = null;
        }

        if ($contentMasterDocumentId && $contentMasterDocumentId == $this->getId()) {
            throw new \Exception('You cannot use the current document as a master document, please choose a different one.');
        }

        $this->contentMasterDocumentId = $contentMasterDocumentId;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getContentMasterDocumentId()
    {
        return $this->contentMasterDocumentId;
    }

    /**
     * @return Document|null
     */
    public function getContentMasterDocument()
    {
        if ($masterDocumentId = $this->getContentMasterDocumentId()) {
            return Document::getById($masterDocumentId);
        }

        return null;
    }

    /**
     * @param Document $document
     *
     * @return $this
     */
    public function setContentMasterDocument($document)
    {
        if ($document instanceof self) {
            $this->setContentMasterDocumentId($document->getId());
        } else {
            $this->setContentMasterDocumentId(null);
        }

        return $this;
    }

    /**
     * @param string $name
     *
     * @return bool
     *
     * @deprecated since v6.7 and will be removed in 7. Use hasEditable() instead.
     */
    public function hasElement($name)
    {
        return $this->hasEditable($name);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasEditable(string $name)
    {
        return $this->getEditable($name) !== null;
    }

    /**
     * @return Editable[]
     *
     * @deprecated since v6.7 and will be removed in 7. Use getEditables() instead.
     */
    public function getElements()
    {
        return $this->getEditables();
    }

    /**
     * @return Editable[]
     */
    public function getEditables(): array
    {
        if ($this->editables === null) {
            $this->setEditables($this->getDao()->getEditables());
        }

        return $this->editables;
    }

    /**
     * @param array $elements
     *
     * @return $this
     *
     * @deprecated since v6.7 and will be removed in 7. Use setEditables() instead.
     */
    public function setElements($elements)
    {
        return $this->setEditables($elements);
    }

    /**
     * @param array|null $editables
     *
     * @return $this
     *
     */
    public function setEditables(?array $editables)
    {
        $this->editables = $editables;

        return $this;
    }

    /**
     * @return Model\Version[]
     */
    public function getVersions()
    {
        if ($this->versions === null) {
            $this->setVersions($this->getDao()->getVersions());
        }

        return $this->versions;
    }

    /**
     * @param array $versions
     *
     * @return $this
     */
    public function setVersions($versions)
    {
        $this->versions = $versions;

        return $this;
    }

    /**
     * @see Document::getFullPath
     *
     * @return string
     */
    public function getHref()
    {
        return $this->getFullPath();
    }

    /**
     * @TODO: remove with $this->elements
     */
    public function __wakeup()
    {
        $this->editables = & $this->elements;

        parent::__wakeup();
    }

    public function __sleep()
    {
        $finalVars = [];
        $parentVars = parent::__sleep();

        $blockedVars = ['inheritedElements', 'inheritedEditables'];

        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }

    /**
     * @param string|null $hostname
     * @param string|null $scheme
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getUrl($hostname = null, $scheme = null)
    {
        if (!$scheme) {
            $scheme = 'http://';
            $requestHelper = \Pimcore::getContainer()->get('pimcore.http.request_helper');
            if ($requestHelper->hasMasterRequest()) {
                $scheme = $requestHelper->getMasterRequest()->getScheme() . '://';
            }
        }

        if (!$hostname) {
            $hostname = \Pimcore\Config::getSystemConfiguration('general')['domain'];
            if (empty($hostname)) {
                if (!$hostname = \Pimcore\Tool::getHostname()) {
                    throw new \Exception('No hostname available');
                }
            }
        }

        $url = $scheme . $hostname . $this->getFullPath();

        $site = \Pimcore\Tool\Frontend::getSiteForDocument($this);
        if ($site instanceof Model\Site && $site->getMainDomain()) {
            $url = $scheme . $site->getMainDomain() . preg_replace('@^' . $site->getRootPath() . '/?@', '/', $this->getRealFullPath());
        }

        return $url;
    }

    /**
     * checks if the document is missing values for required editables
     *
     * @return bool|null
     */
    public function getMissingRequiredEditable()
    {
        return $this->missingRequiredEditable;
    }

    /**
     * @param bool|null $missingRequiredEditable
     *
     * @return $this
     */
    public function setMissingRequiredEditable($missingRequiredEditable)
    {
        if ($missingRequiredEditable !== null) {
            $missingRequiredEditable = (bool) $missingRequiredEditable;
        }

        $this->missingRequiredEditable = $missingRequiredEditable;

        return $this;
    }

    /**
     * @internal
     *
     * @return bool
     */
    public function supportsContentMaster(): bool
    {
        return $this->supportsContentMaster;
    }

    /**
     * Validates if there is a missing value for required editable
     */
    protected function checkMissingRequiredEditable()
    {
        //Allowed tags for required check
        $allowedTypes = ['input', 'wysiwyg', 'textarea', 'numeric'];

        if ($this->getMissingRequiredEditable() === null) {
            /** @var EditableUsageResolver $editableUsageResolver */
            $editableUsageResolver = \Pimcore::getContainer()->get(EditableUsageResolver::class);
            try {
                $documentCopy = Service::cloneMe($this);
                if ($documentCopy instanceof self) {
                    // rendering could fail if the controller/action doesn't exist, in this case we can skip the required check
                    $editableNames = $editableUsageResolver->getUsedEditableNames($documentCopy);
                    foreach ($editableNames as $editableName) {
                        $editable = $documentCopy->getEditable($editableName);
                        if ($editable instanceof Editable && in_array($editable->getType(), $allowedTypes)) {
                            $editableConfig = $editable->getConfig();
                            if ($editable->isEmpty() && isset($editableConfig['required']) && $editableConfig['required'] == true) {
                                $this->setMissingRequiredEditable(true);
                                break;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // noting to do, as rendering the document failed for whatever reason
            }
        }
    }
}
