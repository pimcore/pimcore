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

use Pimcore\Model\Redirect;
use Pimcore\Model\Site;
use Pimcore\Model\Tool\Targeting\TargetGroup;

/**
 * @method \Pimcore\Model\Document\Page\Dao getDao()
 */
class Page extends TargetingDocument
{
    /**
     * Contains the title of the page (meta-title)
     *
     * @var string
     */
    protected $title = '';

    /**
     * Contains the description of the page (meta-description)
     *
     * @var string
     */
    protected $description = '';

    /**
     * @var array
     */
    protected $metaData = [];

    /**
     * Static type of the document
     *
     * @var string
     */
    protected $type = 'page';

    /**
     * @var string|null
     */
    protected $prettyUrl;

    /**
     * Comma separated IDs of target groups
     *
     * @var string
     */
    protected $targetGroupIds = '';

    /**
     * @inheritdoc
     */
    protected function doDelete()
    {
        // check for redirects pointing to this document, and delete them too
        $redirects = new Redirect\Listing();
        $redirects->setCondition('target = ?', $this->getId());
        $redirects->load();

        foreach ($redirects->getRedirects() as $redirect) {
            $redirect->delete();
        }

        if ($site = Site::getByRootId($this->getId())) {
            $site->delete();
        }

        parent::doDelete();
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return \Pimcore\Tool\Text::removeLineBreaks($this->title);
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = str_replace("\n", ' ', $description);

        return $this;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @param array $metaData
     *
     * @return $this
     */
    public function setMetaData($metaData)
    {
        $this->metaData = $metaData;

        return $this;
    }

    /**
     * @return array
     */
    public function getMetaData()
    {
        return $this->metaData;
    }

    /**
     * @inheritDoc
     */
    public function getFullPath(bool $force = false)
    {
        $path = parent::getFullPath($force);

        // do not use pretty url's when in admin, the current document is wrapped by a hardlink or this document isn't in the current site
        if (!\Pimcore::inAdmin() && !($this instanceof Hardlink\Wrapper\WrapperInterface) && \Pimcore\Tool\Frontend::isDocumentInCurrentSite($this)) {
            // check for a pretty url
            $prettyUrl = $this->getPrettyUrl();
            if (!empty($prettyUrl) && strlen($prettyUrl) > 1) {
                return $prettyUrl;
            }
        }

        return $path;
    }

    /**
     * @param string $prettyUrl
     *
     * @return $this
     */
    public function setPrettyUrl($prettyUrl)
    {
        $this->prettyUrl = '/' . trim($prettyUrl, ' /');
        if (strlen($this->prettyUrl) < 2) {
            $this->prettyUrl = null;
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPrettyUrl()
    {
        return $this->prettyUrl;
    }

    /**
     * Set linked Target Groups as set in properties panel as list of IDs
     *
     * @param string|array $targetGroupIds
     */
    public function setTargetGroupIds($targetGroupIds)
    {
        if (is_array($targetGroupIds)) {
            $targetGroupIds = implode(',', $targetGroupIds);
        }

        $targetGroupIds = trim($targetGroupIds, ' ,');

        if (!empty($targetGroupIds)) {
            $targetGroupIds = ',' . $targetGroupIds . ',';
        }

        $this->targetGroupIds = $targetGroupIds;
    }

    /**
     * Get serialized list of Target Group IDs
     *
     * @return string
     */
    public function getTargetGroupIds(): string
    {
        return $this->targetGroupIds;
    }

    /**
     * Set assigned target groups
     *
     * @param TargetGroup[]|int[] $targetGroups
     */
    public function setTargetGroups(array $targetGroups)
    {
        $ids = array_map(function ($targetGroup) {
            if (is_numeric($targetGroup)) {
                return (int)$targetGroup;
            } elseif ($targetGroup instanceof TargetGroup) {
                return $targetGroup->getId();
            }

            return null;
        }, $targetGroups);

        $ids = array_filter($ids, function ($id) {
            return null !== $id && $id > 0;
        });

        $this->setTargetGroupIds($ids);
    }

    /**
     * Return list of assigned target groups (via properties panel)
     *
     * @return TargetGroup[]
     */
    public function getTargetGroups(): array
    {
        $ids = explode(',', $this->targetGroupIds);

        $targetGroups = array_map(function ($id) {
            $id = trim($id);
            if (!empty($id)) {
                $targetGroup = TargetGroup::getById($id);
                if ($targetGroup) {
                    return $targetGroup;
                }
            }
        }, $ids);

        $targetGroups = array_filter($targetGroups);

        return $targetGroups;
    }

    /**
     * @param bool $hdpi
     *
     * @return string
     */
    public function getPreviewImageFilesystemPath($hdpi = false)
    {
        $suffix = '';
        if ($hdpi) {
            $suffix = '@2x';
        }

        return PIMCORE_SYSTEM_TEMP_DIRECTORY . '/document-page-previews/document-page-screenshot-' . $this->getId() . $suffix . '.jpg';
    }
}
