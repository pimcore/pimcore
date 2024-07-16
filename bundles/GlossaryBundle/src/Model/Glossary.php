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

namespace Pimcore\Bundle\GlossaryBundle\Model;

use Pimcore\Bundle\GlossaryBundle\Model\Glossary\Dao;
use Pimcore\Model\AbstractModel;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Model\Site;

/**
 * @method Dao getDao()
 * @method void delete()
 * @method void save()
 */
class Glossary extends AbstractModel
{
    /**
     * @internal
     *
     */
    protected ?int $id = null;

    /**
     * @internal
     *
     */
    protected ?string $text = null;

    /**
     * @internal
     *
     */
    protected ?string $link = null;

    /**
     * @internal
     *
     */
    protected ?string $abbr = null;

    /**
     * @internal
     *
     */
    protected ?string $language = null;

    /**
     * @internal
     *
     */
    protected bool $casesensitive = false;

    /**
     * @internal
     *
     */
    protected bool $exactmatch = false;

    /**
     * @internal
     *
     */
    protected ?int $site = null;

    /**
     * @internal
     *
     */
    protected ?int $creationDate = null;

    /**
     * @internal
     *
     */
    protected ?int $modificationDate = null;

    public static function getById(int $id): ?Glossary
    {
        try {
            $glossary = new self();
            $glossary->setId($id);
            $glossary->getDao()->getById();

            return $glossary;
        } catch (NotFoundException $e) {
            return null;
        }
    }

    public static function create(): Glossary
    {
        $glossary = new self();
        $glossary->save();

        return $glossary;
    }

    /**
     * @return $this
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * @return $this
     */
    public function setLink(string $link): static
    {
        $this->link = $link;

        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    /**
     * @return $this
     */
    public function setAbbr(string $abbr): static
    {
        $this->abbr = $abbr;

        return $this;
    }

    public function getAbbr(): ?string
    {
        return $this->abbr;
    }

    /**
     * @return $this
     */
    public function setLanguage(?string $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    /**
     * @return $this
     */
    public function setCasesensitive(bool $casesensitive): static
    {
        $this->casesensitive = $casesensitive;

        return $this;
    }

    public function getCasesensitive(): bool
    {
        return $this->casesensitive;
    }

    /**
     * @return $this
     */
    public function setExactmatch(bool $exactmatch): static
    {
        $this->exactmatch = $exactmatch;

        return $this;
    }

    public function getExactmatch(): bool
    {
        return $this->exactmatch;
    }

    /**
     * @return $this
     */
    public function setSite(null|int|Site $site): static
    {
        if ($site instanceof Site) {
            $site = $site->getId();
        }
        $this->site = $site;

        return $this;
    }

    public function getSite(): ?int
    {
        return $this->site;
    }

    /**
     * @return $this
     */
    public function setModificationDate(int $modificationDate): static
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    /**
     * @return $this
     */
    public function setCreationDate(int $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }
}
