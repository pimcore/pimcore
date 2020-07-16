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

use Pimcore\Document\Tag\TagUsageResolver;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\DocumentEvent;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Tag\Loader\TagLoaderInterface;

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
     */
    protected $elements = null;

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
     * @var null|bool
     */
    protected $missingRequiredEditable = null;

    /**
     * @var array
     */
    protected $inheritedElements = [];

    /**
     * @param array $params additional parameters (e.g. "versionNote" for the version note)
     *
     * @throws \Exception
     */
    protected function update($params = [])
    {

        // update elements
        $this->getElements();
        $this->getDao()->deleteAllElements();

        if (is_array($this->getElements()) and count($this->getElements()) > 0) {
            foreach ($this->getElements() as $name => $element) {
                if (!$element->getInherited()) {
                    $element->setDao(null);
                    $element->setDocumentId($this->getId());
                    $element->save();
                }
            }
        }

        // scheduled tasks are saved in $this->saveVersion();

        // load data which must be requested
        $this->getProperties();
        $this->getElements();

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
                $version = $this->doSaveVersion($versionNote, $saveOnlyVersion);
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
    public function delete(bool $isNested = false)
    {
        $this->beginTransaction();

        try {
            $versions = $this->getVersions();
            foreach ($versions as $version) {
                $version->delete();
            }

            // remove all tasks
            $this->getDao()->deleteAllTasks();

            parent::delete(true);

            $this->commit();
        } catch (\Exception $e) {
            $this->rollBack();
            \Pimcore::getEventDispatcher()->dispatch(DocumentEvents::POST_DELETE_FAILURE, new DocumentEvent($this));
            Logger::error($e);
            throw $e;
        }
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

        foreach ($this->getElements() as $element) {
            $tags = $element->getCacheTags($this, $tags);
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

        foreach ($this->getElements() as $element) {
            $dependencies = array_merge($dependencies, $element->resolveDependencies());
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
     * @param string $data
     *
     * @return $this
     */
    public function setRawElement($name, $type, $data)
    {
        try {
            if ($type) {
                /** @var TagLoaderInterface $loader */
                $loader = \Pimcore::getContainer()->get('pimcore.implementation_loader.document.tag');
                $element = $loader->build($type);

                $this->elements = $this->elements ?? [];
                $this->elements[$name] = $element;
                $this->elements[$name]->setDataFromEditmode($data);
                $this->elements[$name]->setName($name);
                $this->elements[$name]->setDocument($this);
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
     * @param Tag $data
     *
     * @return $this
     */
    public function setElement($name, $data)
    {
        $this->elements[$name] = $data;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function removeElement($name)
    {
        if (isset($this->elements[$name])) {
            unset($this->elements[$name]);
        }

        return $this;
    }

    /**
     * Get an element with the given key/name
     *
     * @param string $name
     *
     * @return Tag|null
     */
    public function getElement($name)
    {
        $elements = $this->getElements();
        if (isset($this->elements[$name])) {
            return $elements[$name];
        }

        if (array_key_exists($name, $this->inheritedElements)) {
            return $this->inheritedElements[$name];
        }

        // check for content master document (inherit data)
        if ($contentMasterDocument = $this->getContentMasterDocument()) {
            if ($contentMasterDocument instanceof self) {
                $inheritedElement = $contentMasterDocument->getElement($name);
                if ($inheritedElement) {
                    $inheritedElement = clone $inheritedElement;
                    $inheritedElement->setInherited(true);
                    $this->inheritedElements[$name] = $inheritedElement;

                    return $inheritedElement;
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
     */
    public function hasElement($name)
    {
        return $this->getElement($name) !== null;
    }

    /**
     * @return Tag[]
     */
    public function getElements()
    {
        if ($this->elements === null) {
            $this->setElements($this->getDao()->getElements());
        }

        return $this->elements;
    }

    /**
     * @param array $elements
     *
     * @return $this
     */
    public function setElements($elements)
    {
        $this->elements = $elements;

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

    public function __sleep()
    {
        $finalVars = [];
        $parentVars = parent::__sleep();

        $blockedVars = ['inheritedElements'];

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
     * Validates if there is a missing value for required editable
     */
    protected function checkMissingRequiredEditable()
    {
        //Allowed tags for required check
        $allowedTypes = ['input', 'wysiwyg', 'textarea', 'numeric'];

        if ($this->getMissingRequiredEditable() === null) {
            /** @var TagUsageResolver $tagUsageResolver */
            $tagUsageResolver = \Pimcore::getContainer()->get(TagUsageResolver::class);
            try {
                $documentCopy = Service::cloneMe($this);
                if ($documentCopy instanceof self) {
                    // rendering could fail if the controller/action doesn't exist, in this case we can skip the required check
                    $tagNames = $tagUsageResolver->getUsedTagnames($documentCopy);
                    foreach ($tagNames as $tagName) {
                        $tag = $documentCopy->getElement($tagName);
                        if ($tag instanceof Tag && in_array($tag->getType(), $allowedTypes)) {
                            $documentOptions = $tag->getOptions();
                            if ($tag->isEmpty() && isset($documentOptions['required']) && $documentOptions['required'] == true) {
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
