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

use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Model\Redirect;

/**
 * @method \Pimcore\Model\Document\Hardlink\Dao getDao()
 */
class Hardlink extends Document
{
    use Document\Traits\ScheduledTasksTrait;
    use Document\Traits\RedirectHelperTrait;

    /**
     * static type of this object
     *
     * @var string
     */
    protected $type = 'hardlink';

    /**
     * @var int
     */
    protected $sourceId;

    /**
     * @var bool
     */
    protected $propertiesFromSource;

    /**
     * @var bool
     */
    protected $childrenFromSource;

    /**
     * @return Document\PageSnippet
     */
    public function getSourceDocument()
    {
        if ($this->getSourceId()) {
            return Document::getById($this->getSourceId());
        }

        return null;
    }

    /**
     * @see Document::resolveDependencies
     *
     * @return array
     */
    public function resolveDependencies()
    {
        $dependencies = parent::resolveDependencies();

        if ($this->getSourceDocument() instanceof Document) {
            $key = 'document_' . $this->getSourceDocument()->getId();

            $dependencies[$key] = [
                'id' => $this->getSourceDocument()->getId(),
                'type' => 'document'
            ];
        }

        return $dependencies;
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

        if ($this->getSourceDocument()) {
            if ($this->getSourceDocument()->getId() != $this->getId() and !array_key_exists($this->getSourceDocument()->getCacheTag(), $tags)) {
                $tags = $this->getSourceDocument()->getCacheTags($tags);
            }
        }

        return $tags;
    }

    /**
     * @param $childrenFromSource
     *
     * @return Hardlink
     */
    public function setChildrenFromSource($childrenFromSource)
    {
        $this->childrenFromSource = (bool) $childrenFromSource;

        return $this;
    }

    /**
     * @return bool
     */
    public function getChildrenFromSource()
    {
        return $this->childrenFromSource;
    }

    /**
     * @param $sourceId
     *
     * @return $this
     */
    public function setSourceId($sourceId)
    {
        $this->sourceId = (int) $sourceId;

        return $this;
    }

    /**
     * @return int
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * @param $propertiesFromSource
     *
     * @return $this
     */
    public function setPropertiesFromSource($propertiesFromSource)
    {
        $this->propertiesFromSource = (bool) $propertiesFromSource;

        return $this;
    }

    /**
     * @return bool
     */
    public function getPropertiesFromSource()
    {
        return $this->propertiesFromSource;
    }

    /**
     * @return array|null|Model\Property[]
     */
    public function getProperties()
    {
        if ($this->properties === null) {
            $properties = parent::getProperties();

            if ($this->getPropertiesFromSource() && $this->getSourceDocument()) {
                $sourceProperties = $this->getSourceDocument()->getProperties();
                foreach ($sourceProperties as &$prop) {
                    $prop = clone $prop; // because of cache
                    $prop->setInherited(true);
                }
                $properties = array_merge($sourceProperties, $properties);
            } elseif ($this->getSourceDocument()) {
                $sourceProperties = $this->getSourceDocument()->getDao()->getProperties(false, true);
                foreach ($sourceProperties as &$prop) {
                    /**
                     * @var Model\Property $prop
                     */
                    $prop = clone $prop; // because of cache
                    $prop->setInherited(true);
                }
                $properties = array_merge($sourceProperties, $properties);
            }

            $this->setProperties($properties);
        }

        return $this->properties;
    }

    /**
     * @param bool $unpublished
     *
     * @return Document[]
     */
    public function getChildren($unpublished = false)
    {
        if ($this->children === null) {
            $children = parent::getChildren($unpublished);

            $sourceChildren = [];
            if ($this->getChildrenFromSource() && $this->getSourceDocument() && !\Pimcore::inAdmin()) {
                $sourceChildren = $this->getSourceDocument()->getChildren($unpublished);
                foreach ($sourceChildren as &$c) {
                    $c = Document\Hardlink\Service::wrap($c);
                    $c->setHardLinkSource($this);
                    $c->setPath(preg_replace('@^' . preg_quote($this->getSourceDocument()->getRealFullPath()) . '@', $this->getRealFullPath(), $c->getRealPath()));
                }
            }

            $children = array_merge($sourceChildren, $children);
            $this->setChildren($children);
        }

        return $this->children;
    }

    /**
     * @inheritdoc
     */
    public function hasChildren($unpublished = false)
    {
        return count($this->getChildren()) > 0;
    }

    /**
     * @inheritdoc
     */
    public function delete(bool $isNested = false)
    {

        // hardlinks cannot have direct children in "real" world, so we have to empty them before we delete it
        $this->children = [];

        // check for redirects pointing to this document, and delete them too
        $redirects = new Redirect\Listing();
        $redirects->setCondition('target = ?', $this->getId());
        $redirects->load();

        foreach ($redirects->getRedirects() as $redirect) {
            $redirect->delete();
        }

        parent::delete($isNested);

        // we re-enable the children functionality by setting them to NULL, if requested they'll be loaded again
        // -> see $this->getChildren() , doesn't make sense when deleting an item but who knows, ... ;-)
        $this->children = null;
    }

    /**
     * @param array $params additional parameters (e.g. "versionNote" for the version note)
     *
     * @throws \Exception
     */
    protected function update($params = [])
    {
        $oldPath = $this->getDao()->getCurrentFullPath();
        $oldDocument = self::getById($this->getId(), true);

        parent::update($params);

        $this->createRedirectForFormerPath($oldPath, $oldDocument);
        $this->saveScheduledTasks();
    }
}
