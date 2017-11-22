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
use Pimcore\Model;
use Pimcore\Model\Document\Targeting\TargetingDocumentInterface;
use Pimcore\Model\Redirect;

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
     * comma separated IDs of personas
     *
     * @var string
     */
    public $personas = '';

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

    protected function update()
    {
        $oldPath = $this->getDao()->getCurrentFullPath();

        parent::update();

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
     * @param string $personas
     */
    public function setPersonas($personas)
    {
        if (is_array($personas)) {
            $personas = implode(',', $personas);
        }
        $personas = trim($personas, ' ,');
        if (!empty($personas)) {
            $personas = ',' . $personas . ',';
        }
        $this->personas = $personas;
    }

    /**
     * @return string
     */
    public function getPersonas()
    {
        return $this->personas;
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
}
