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

namespace Pimcore\Bundle\SimpleBackendSearchBundle\Model\Search\Backend\Data;

use Pimcore\Model\Element;

/**
 * @internal
 */
class Id
{
    protected int $id;

    protected string $type;

    public function __construct(Element\ElementInterface $webResource)
    {
        $this->id = $webResource->getId();
        $this->type = Element\Service::getElementType($webResource) ?: 'unknown';
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
