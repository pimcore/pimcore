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

namespace Pimcore\Model;

use Pimcore\Model\Exception\NotFoundException;

/**
 * @method \Pimcore\Model\Glossary\Dao getDao()
 * @method void delete()
 * @method void save()
 */
class Glossary extends AbstractModel
{
    /**
     * @internal
     *
     * @var int|null
     */
    protected $id;

    /**
     * @internal
     *
     * @var string|null
     */
    protected $text;

    /**
     * @internal
     *
     * @var string|null
     */
    protected $link;

    /**
     * @internal
     *
     * @var string|null
     */
    protected $abbr;

    /**
     * @internal
     *
     * @var string|null
     */
    protected $language;

    /**
     * @internal
     *
     * @var bool
     */
    protected $casesensitive = false;

    /**
     * @internal
     *
     * @var bool
     */
    protected $exactmatch = false;

    /**
     * @internal
     *
     * @var int|null
     */
    protected $site;

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
     * @param int $id
     *
     * @return self|null
     */
    public static function getById($id)
    {
        try {
            $glossary = new self();
            $glossary->setId((int)$id);
            $glossary->getDao()->getById();

            return $glossary;
        } catch (NotFoundException $e) {
            return null;
        }
    }

    /**
     * @return Glossary
     */
    public static function create()
    {
        $glossary = new self();
        $glossary->save();

        return $glossary;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $text
     *
     * @return $this
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $link
     *
     * @return $this
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param string $abbr
     *
     * @return $this
     */
    public function setAbbr($abbr)
    {
        $this->abbr = $abbr;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAbbr()
    {
        return $this->abbr;
    }

    /**
     * @param string $language
     *
     * @return $this
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param bool $casesensitive
     *
     * @return $this
     */
    public function setCasesensitive($casesensitive)
    {
        $this->casesensitive = (bool) $casesensitive;

        return $this;
    }

    /**
     * @return bool
     */
    public function getCasesensitive()
    {
        return $this->casesensitive;
    }

    /**
     * @param bool $exactmatch
     *
     * @return $this
     */
    public function setExactmatch($exactmatch)
    {
        $this->exactmatch = (bool) $exactmatch;

        return $this;
    }

    /**
     * @return bool
     */
    public function getExactmatch()
    {
        return $this->exactmatch;
    }

    /**
     * @param Site|int $site
     *
     * @return $this
     */
    public function setSite($site)
    {
        if ($site instanceof Site) {
            $site = $site->getId();
        }
        $this->site = (int) $site;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getSite()
    {
        return $this->site;
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
     * @return int
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }
}
