<?php
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

namespace Pimcore\Event\Cache\Core;

use Symfony\Component\EventDispatcher\Event;

class ResultEvent extends Event
{
    /**
     * @var bool
     */
    protected $result;

    /**
     * @param bool $result
     */
    public function __construct($result = true)
    {
        $this->setResult($result);
    }

    /**
     * @return bool
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param bool $result
     */
    public function setResult($result)
    {
        $this->result = (bool)$result;
    }
}
