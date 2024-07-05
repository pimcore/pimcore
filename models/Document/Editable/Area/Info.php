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

namespace Pimcore\Model\Document\Editable\Area;

use Exception;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Editable;
use Symfony\Component\HttpFoundation\Request;

class Info
{
    /**
     * @internal
     *
     */
    protected ?string $id = null;

    /**
     * @internal
     *
     */
    protected ?Editable $editable = null;

    /**
     * @internal
     *
     */
    protected array $params = [];

    /**
     * @internal
     *
     */
    protected ?Request $request = null;

    /**
     * @internal
     *
     */
    protected ?string $type = null;

    /**
     * @internal
     *
     */
    protected ?int $index = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getEditable(): ?Editable
    {
        return $this->editable;
    }

    public function setEditable(Editable $editable): void
    {
        $this->editable = $editable;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getParam(string $name): mixed
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        }

        return null;
    }

    public function setParam(string $name, mixed $value): static
    {
        $this->params[$name] = $value;

        return $this;
    }

    public function setParams(array $params): static
    {
        $this->params = $params;

        return $this;
    }

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    public function setRequest(Request $request): static
    {
        $this->request = $request;

        return $this;
    }

    public function setIndex(?int $index): static
    {
        $this->index = $index;

        return $this;
    }

    public function getIndex(): ?int
    {
        return $this->index;
    }

    public function getDocument(): Document\PageSnippet
    {
        return $this->editable->getDocument();
    }

    /**
     *
     *
     * @throws Exception
     */
    public function getDocumentElement(string $name, string $type = ''): ?Editable
    {
        $editable = null;
        $document = $this->getDocument();

        if ($document instanceof Document\PageSnippet) {
            $name = Editable::buildEditableName($type, $name, $document);
            $editable = $document->getEditable($name);
        }

        return $editable;
    }
}
