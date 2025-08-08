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
