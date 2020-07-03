<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\DependencyInjection;

use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Service locator exposing all of its services as collection
 */
class CollectionServiceLocator extends ServiceLocator implements \IteratorAggregate
{
    /**
     * @var array
     */
    private $ids;

    public function __construct($factories)
    {
        $this->ids = array_keys($factories);

        parent::__construct($factories);
    }

    public function all(): array
    {
        return array_map(function ($id) {
            return $this->get($id);
        }, $this->ids);
    }

    public function getIterator()
    {
        foreach ($this->ids as $id) {
            yield $this->get($id);
        }
    }
}
