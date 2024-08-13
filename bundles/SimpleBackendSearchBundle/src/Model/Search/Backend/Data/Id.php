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
