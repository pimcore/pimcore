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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Search\Backend\Data;

use Pimcore\Model\Element;

/**
 * @internal
 */
class Id
{
    /**
     * @var int
     */
    protected int $id;

    /**
     * @var string
     */
    protected string $type;

    /**
     * @param Element\ElementInterface $webResource
     */
    public function __construct(Element\ElementInterface $webResource)
    {
        $this->id = $webResource->getId();
        $this->type = Element\Service::getType($webResource) ?: 'unknown';
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}
