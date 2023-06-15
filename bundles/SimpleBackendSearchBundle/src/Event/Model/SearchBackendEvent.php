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

namespace Pimcore\Bundle\SimpleBackendSearchBundle\Event\Model;

use Pimcore\Bundle\SimpleBackendSearchBundle\Model\Search\Backend\Data;
use Symfony\Contracts\EventDispatcher\Event;

class SearchBackendEvent extends Event
{
    protected Data $data;

    /**
     * Data constructor.
     *
     */
    public function __construct(Data $data)
    {
        $this->data = $data;
    }

    public function getData(): Data
    {
        return $this->data;
    }

    public function setData(Data $data): void
    {
        $this->data = $data;
    }
}
