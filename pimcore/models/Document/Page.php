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

use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model\Document\Targeting\TargetingDocumentInterface;
use Pimcore\Model\Redirect;
use Pimcore\Model\Tool\Targeting\TargetGroup;

/**
 * @method \Pimcore\Model\Document\Page\Dao getDao()
 */
class Page extends TargetingDocument
{
    /**
     * @deprecated Will be removed in Pimcore 6.
     */
    const PERSONA_ELEMENT_PREFIX_PREFIXPART = TargetingDocumentInterface::TARGET_GROUP_ELEMENT_PREFIX;

    /**
     * @deprecated Will be removed in Pimcore 6.
     */
    const PERSONA_ELEMENT_PREFIX_SUFFIXPART = TargetingDocumentInterface::TARGET_GROUP_ELEMENT_SUFFIX;

    /**
     * Contains the title of the page (meta-title)
     *
     * @var string
     */
    public $title = '';

    /**
     * Contains the description of the page (meta-description)
     *
     * @var string
     */
    public $description = '';

    /**
     * @var array
     */
    public $metaData = [];

    /**
     * Static type of the document
     *
     * @var string
     */
    public $type = 'page';

    /**
     * @var string
     */
    public $prettyUrl;

    /**
     * Comma separated IDs of target groups
     *
     * @var string
     */
    public $targetGroupIds = '';

    /**
     * @throws \Exception
     */
    public function delete()
    {
        if ($this->getId() == 1) {
            throw new \Exception('root-node cannot be deleted');
        }

        // check for redirects pointing to this document, and delete them too
        $redirects = new Redirect\Listing();
        $redirects->setCondition('target = ?', $this->getId());
        $redirects->load();

        foreach ($redirects->getRedirects() as $redirect) {
            $redirect->delete();
        }

        parent::delete();
    }

    /**
     * @param array $params additional parameters (e.g. "versionNote" for the version note)
     *
     * @throws \Exception
     */
    protected function update($params = [])
    {
        $oldPath = $this->getDao()->getCurrentFullPath();

        parent::update($params);

        $config = \Pimcore\Config::getSystemConfig();
        if ($oldPath && $config->documents->createredirectwhenmoved && $oldPath != $this->getRealFullPath()) {

            // check if the current page is in a site
            $siteCheckQuery = "
                SELECT documentsites.id FROM documents dd 
                INNER JOIN (
                    SELECT s.id, mainDomain, concat(d.path, d.key, '%') fullPath FROM `sites` s INNER JOIN documents d ON s.rootId = d.id
                  ) documentsites
                  ON dd.path LIKE documentsites.fullPath
                WHERE dd.id = ?";

            $siteId = Db::get()->fetchOne($siteCheckQuery, [$this->getId()]);

            // create redirect for old path
            $redirect = new Redirect();
            $redirect->setType(Redirect::TYPE_PATH);
            $redirect->setRegex(true);
            $redirect->setTarget($this->getId());
            $redirect->setSource('@' . $oldPath . '/?@');
            $redirect->setStatusCode(301);
            $redirect->setExpiry(time() + 86400 * 60); // this entry is removed automatically after 60 days

            if ($siteId) {
                $redirect->setSourceSite($siteId);
            }

            $redirect->save();
        }
    }

    /**
     * getProperty method should be used instead
     *
     * @deprecated
     *
     * @return string
     */
    public function getName()
    {
        return $this->getProperty('navigation_name');
    }

    /**
     * setProperty method should be used instead
     *
     * @deprecated
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->setProperty('navigation_name', 'text', $name, false);

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @deprecated
     *
     * @return string
     */
    public function getKeywords()
    {
        // keywords are not supported anymore
        Logger::info('getKeywords() is deprecated and will be removed in the future!');

        return '';
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
     * @deprecated
     *
     * @param string $keywords
     *
     * @return $this
     */
    public function setKeywords($keywords)
    {
        // keywords are not supported anymore
        Logger::info('setKeywords() is deprecated and will be removed in the future!');

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
     * @param $metaData
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

    public function getFullPath()
    {
        $path = parent::getFullPath();

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
     * @param $prettyUrl
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
     * @return string
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
     * @deprecated Use setTargetGroupIds instead. Will be removed in Pimcore 6.
     *
     * @param string $personas
     */
    public function setPersonas($personas)
    {
        $this->setTargetGroupIds((array)$personas);
    }

    /**
     * @deprecated Use getTargetGroupIds instead. Will be removed in Pimcore 6.
     *
     * @return string
     */
    public function getPersonas()
    {
        return $this->getTargetGroupIds();
    }

    /**
     * @deprecated Use getTargetGroupElementPrefix instead. Will be removed in Pimcore 6.
     */
    public function getPersonaElementPrefix($personaId = null)
    {
        return $this->getTargetGroupElementPrefix(null !== $personaId ? (int)$personaId : null);
    }

    /**
     * @deprecated Use getTargetGroupElementName instead. Will be removed in Pimcore 6.
     */
    public function getPersonaElementName($name)
    {
        return $this->getTargetGroupElementName((string)$name);
    }

    /**
     * @deprecated Use setUseTargetGroup instead. Will be removed in Pimcore 6.
     */
    public function setUsePersona($usePersona)
    {
        $this->setUseTargetGroup(null !== $usePersona ? (int)$usePersona : null);
    }

    /**
     * @deprecated Use getUseTargetGroup instead. Will be removed in Pimcore 6.
     */
    public function getUsePersona()
    {
        return $this->getUseTargetGroup();
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
