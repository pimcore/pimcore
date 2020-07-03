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

namespace Pimcore\Navigation\Page;

use Pimcore\Model;

class Document extends Url
{
    /**
     * @var string
     */
    protected $_accesskey;

    /**
     * @var string
     */
    protected $_tabindex;

    /**
     * @var string
     */
    protected $_relation;

    /**
     * @var int
     */
    protected $_documentId;

    /**
     * @var string
     */
    protected $documentType;

    /**
     * @var string
     */
    protected $realFullPath;

    /**
     * @var array
     */
    protected $customSettings = [];

    /**
     * @param string $tabindex
     *
     * @return $this
     */
    public function setTabindex($tabindex)
    {
        $this->_tabindex = $tabindex;

        return $this;
    }

    /**
     * @return string
     */
    public function getTabindex()
    {
        return $this->_tabindex;
    }

    /**
     * @param string|null $character
     *
     * @return $this
     */
    public function setAccesskey($character = null)
    {
        $this->_accesskey = $character;

        return $this;
    }

    /**
     * @return string
     */
    public function getAccesskey()
    {
        return $this->_accesskey;
    }

    /**
     * @param string $relation
     *
     * @return $this
     */
    public function setRelation($relation)
    {
        $this->_relation = $relation;

        return $this;
    }

    /**
     * @return string
     */
    public function getRelation()
    {
        return $this->_relation;
    }

    /**
     * @param Model\Document $document
     *
     * @return $this
     */
    public function setDocument($document)
    {
        $this->setDocumentId($document->getId());
        $this->setDocumentType($document->getType());
        $this->setRealFullPath($document->getRealFullPath());

        return $this;
    }

    /**
     * @return Model\Document|null
     */
    public function getDocument()
    {
        $docId = $this->getDocumentId();
        if ($docId) {
            $doc = Model\Document::getById($docId);
            if ($doc instanceof Model\Document\Hardlink) {
                $doc = Model\Document\Hardlink\Service::wrap($doc);
            }

            return $doc;
        }

        return null;
    }

    /**
     * @return int
     */
    public function getDocumentId()
    {
        return $this->_documentId;
    }

    /**
     * @param int $documentId
     */
    public function setDocumentId($documentId)
    {
        $this->_documentId = $documentId;
    }

    /**
     * @return string
     */
    public function getDocumentType()
    {
        return $this->documentType;
    }

    /**
     * @param string $documentType
     */
    public function setDocumentType($documentType)
    {
        $this->documentType = $documentType;
    }

    /**
     * @return string
     */
    public function getRealFullPath()
    {
        return $this->realFullPath;
    }

    /**
     * @param string $realFullPath
     */
    public function setRealFullPath($realFullPath)
    {
        $this->realFullPath = $realFullPath;
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return $this
     */
    public function setCustomSetting($name, $value)
    {
        $this->customSettings[$name] = $value;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function getCustomSetting($name)
    {
        if (array_key_exists($name, $this->customSettings)) {
            return $this->customSettings[$name];
        }

        return null;
    }
}
