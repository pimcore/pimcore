<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\DataObject\Data;

use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;

class ExternalImage implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;

    protected ?string $url = null;

    public function __construct(?string $url = null)
    {
        $this->url = $url;
        $this->markMeDirty();
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
        $this->markMeDirty();
    }

    public function __toString(): string
    {
        return (is_null($this->url)) ? '' : $this->url;
    }
}
