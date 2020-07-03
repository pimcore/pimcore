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
 * @package    Glossary
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model;

/**
 * @method \Pimcore\Model\Glossary\Dao getDao()
 * @method void delete()
 * @method void save()
 */
class Glossary extends AbstractModel
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $text;

    /**
     * @var string
     */
    public $link;

    /**
     * @var string
     */
    public $abbr;

    /**
     * @var string
     */
    public $acronym;

    /**
     * @var string
     */
    public $language;

    /**
     * @var bool
     */
    public $casesensitive;

    /**
     * @var bool
     */
    public $exactmatch;

    /**
     * @var int
     */
    public $site;

    /**
     * @var int
     */
    public $creationDate;

    /**
     * @var int
     */
    public $modificationDate;

    /**
     * @param int $id
     *
     * @return self|null
     */
    public static function getById($id)
    {
        try {
            $glossary = new self();
            $glossary->setId(intval($id));
            $glossary->getDao()->getById();

            return $glossary;
        } catch (\Exception $e) {
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
     * @return int
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
     * @return string
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
     * @return string
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
     * @return string
     */
    public function getAbbr()
    {
        return $this->abbr;
    }

    /**
     * @param string $acronym
     *
     * @return $this
     */
    public function setAcronym($acronym)
    {
        $this->acronym = $acronym;

        return $this;
    }

    /**
     * @return string
     */
    public function getAcronym()
    {
        return $this->acronym;
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
     * @return string
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
     * @return int
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
     * @return int
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
