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

namespace Pimcore\Event\Model\Ecommerce\IndexService;

use Symfony\Component\EventDispatcher\Event;


/**
 * Class PreSendRequestEvent
 * @package Pimcore\Event\Model\Ecommerce\IndexService
 */
class PreSendRequestEvent extends Event
{

    /**
     * @var bool
     */
    protected $stopRequest = false;

    /**
     * @var array
     */
    protected $params;

    /**
     * PreSendRequestEvent constructor.
     * @param array $params
     */
    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * @return bool
     */
    public function getStopRequest(): bool
    {
        return $this->stopRequest;
    }

    /**
     * @param bool $stopRequest
     */
    public function setStopRequest(bool $stopRequest): void
    {
        $this->stopRequest = $stopRequest;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param array $params
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

}
