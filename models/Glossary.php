<?php
declare(strict_types=1);

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
    protected ?int $id;

    /**
     * @internal
     *
     * @var string|null
     */
    protected ?string $text;

    /**
     * @internal
     *
     * @var string|null
     */
    protected ?string $link;

    /**
     * @internal
     *
     * @var string|null
     */
    protected ?string $abbr;

    /**
     * @internal
     *
     * @var string|null
     */
    protected ?string $language;

    /**
     * @internal
     *
     * @var bool
     */
    protected bool $casesensitive = false;

    /**
     * @internal
     *
     * @var bool
     */
    protected bool $exactmatch = false;

    /**
     * @internal
     *
     * @var int|null
     */
    protected ?int $site;

    /**
     * @internal
     *
     * @var int|null
     */
    protected ?int $creationDate;

    /**
     * @internal
     *
     * @var int|null
     */
    protected ?int $modificationDate;

    /**
     * @param int $id
     *
     * @return self|null
     */
    public static function getById(int $id): ?Glossary
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
    public static function create(): Glossary
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
    public function setId(int $id): static
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param string $text
     *
     * @return $this
     */
    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * @param string $link
     *
     * @return $this
     */
    public function setLink(string $link): static
    {
        $this->link = $link;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLink(): ?string
    {
        return $this->link;
    }

    /**
     * @param string $abbr
     *
     * @return $this
     */
    public function setAbbr(string $abbr): static
    {
        $this->abbr = $abbr;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAbbr(): ?string
    {
        return $this->abbr;
    }

    /**
     * @param string $language
     *
     * @return $this
     */
    public function setLanguage(string $language): static
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLanguage(): ?string
    {
        return $this->language;
    }

    /**
     * @param bool $casesensitive
     *
     * @return $this
     */
    public function setCasesensitive(bool $casesensitive): static
    {
        $this->casesensitive = (bool) $casesensitive;

        return $this;
    }

    /**
     * @return bool
     */
    public function getCasesensitive(): bool
    {
        return $this->casesensitive;
    }

    /**
     * @param bool $exactmatch
     *
     * @return $this
     */
    public function setExactmatch(bool $exactmatch): static
    {
        $this->exactmatch = (bool) $exactmatch;

        return $this;
    }

    /**
     * @return bool
     */
    public function getExactmatch(): bool
    {
        return $this->exactmatch;
    }

    /**
     * @param int|Site $site
     *
     * @return $this
     */
    public function setSite(Site|int $site): static
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
    public function getSite(): ?int
    {
        return $this->site;
    }

    /**
     * @param int $modificationDate
     *
     * @return $this
     */
    public function setModificationDate(int $modificationDate): static
    {
        $this->modificationDate = (int) $modificationDate;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    /**
     * @param int $creationDate
     *
     * @return $this
     */
    public function setCreationDate(int $creationDate): static
    {
        $this->creationDate = (int) $creationDate;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }
}
