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

namespace Pimcore\Model\Document;

use Pimcore\Model;

/**
 * @method bool isWriteable()
 * @method string getWriteTarget()
 * @method \Pimcore\Model\Document\DocType\Dao getDao()
 * @method void delete()
 * @method void save()
 */
class DocType extends Model\AbstractModel
{
    /**
     * ID of the document-type
     *
     * @internal
     *
     * @var string|null
     */
    protected $id;

    /**
     * Name of the document-type
     *
     * @internal
     *
     * @var string
     */
    protected $name;

    /**
     * Group of document-types
     *
     * @internal
     *
     * @var string
     */
    protected $group;

    /**
     * The specified controller
     *
     * @internal
     *
     * @var string
     */
    protected $controller;

    /**
     * The specified template
     *
     * @internal
     *
     * @var string|null
     */
    protected $template;

    /**
     * Type, must be one of the following: page,snippet,email
     *
     * @internal
     *
     * @var string
     */
    protected $type;

    /**
     * @internal
     *
     * @var int
     */
    protected $priority = 0;

    /**
     * @internal
     *
     * @var int|null
     */
    protected $creationDate;

    /**
     * @internal
     *
     * @var int|null
     */
    protected $modificationDate;

    /**
     * @internal
     *
     * @var null|int
     */
    protected $staticGeneratorEnabled;

    /**
     * Static helper to retrieve an instance of Document\DocType by the given ID
     *
     * @param string $id
     *
     * @return self|null
     */
    public static function getById($id)
    {
        if (empty($id)) {
            return null;
        }

        try {
            $docType = new self();
            $docType->getDao()->getById($id);

            return $docType;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Shortcut to quickly create a new instance
     *
     * @return DocType
     */
    public static function create()
    {
        $type = new self();
        $type->save();

        return $type;
    }

    /**
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
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
     * @param string $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $group
     *
     * @return $this
     */
    public function setGroup($group)
    {
        $this->group = $group;

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
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param int $priority
     *
     * @return $this
     */
    public function setPriority($priority)
    {
        $this->priority = (int) $priority;

        return $this;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param int $modificationDate
     *
     * @return $this
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = (int) $modificationDate;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @param int $creationDate
     *
     * @return $this
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = (int) $creationDate;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @return null|int
     */
    public function getStaticGeneratorEnabled(): ?int
    {
        return $this->staticGeneratorEnabled;
    }

    /**
     * @param null|int $staticGeneratorEnabled
     */
    public function setStaticGeneratorEnabled(?int $staticGeneratorEnabled): void
    {
        $this->staticGeneratorEnabled = $staticGeneratorEnabled;
    }

    public function __clone()
    {
        if ($this->dao) {
            $this->dao = clone $this->dao;
            $this->dao->setModel($this);
        }
    }
}
