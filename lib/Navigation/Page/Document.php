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

namespace Pimcore\Navigation\Page;

use Pimcore\Model;

class Document extends Url
{
    protected ?string $_accesskey = null;

    protected ?string $_tabindex = null;

    protected ?string $_relation = null;

    protected int $_documentId = 0;

    protected string $documentType = '';

    protected string $realFullPath = '';

    protected array $customSettings = [];

    public function setTabindex(?string $tabindex): static
    {
        $this->_tabindex = $tabindex;

        return $this;
    }

    public function getTabindex(): ?string
    {
        return $this->_tabindex;
    }

    public function setAccesskey(?string $character = null): static
    {
        $this->_accesskey = $character;

        return $this;
    }

    public function getAccesskey(): ?string
    {
        return $this->_accesskey;
    }

    public function setRelation(?string $relation): static
    {
        $this->_relation = $relation;

        return $this;
    }

    public function getRelation(): ?string
    {
        return $this->_relation;
    }

    public function setDocument(Model\Document $document): static
    {
        $this->setDocumentId($document->getId());
        $this->setDocumentType($document->getType());
        $this->setRealFullPath($document->getRealFullPath());

        return $this;
    }

    public function getDocument(): ?Model\Document
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

    public function getDocumentId(): int
    {
        return $this->_documentId;
    }

    public function setDocumentId(int $documentId): void
    {
        $this->_documentId = $documentId;
    }

    public function getDocumentType(): string
    {
        return $this->documentType;
    }

    public function setDocumentType(string $documentType): void
    {
        $this->documentType = $documentType;
    }

    public function getRealFullPath(): string
    {
        return $this->realFullPath;
    }

    public function setRealFullPath(string $realFullPath): void
    {
        $this->realFullPath = $realFullPath;
    }

    public function setCustomSetting(string $name, mixed $value): static
    {
        $this->customSettings[$name] = $value;

        return $this;
    }

    public function getCustomSetting(string $name): mixed
    {
        if (array_key_exists($name, $this->customSettings)) {
            return $this->customSettings[$name];
        }

        return null;
    }
}
