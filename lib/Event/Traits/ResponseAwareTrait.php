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

namespace Pimcore\Event\Traits;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Trait for all events handling responses. Taken from GetResponseEvent.
 */
trait ResponseAwareTrait
{
    /**
     * The response object.
     *
     */
    protected Response $response;

    /**
     * Returns the response object.
     *
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * Sets a response and stops event propagation.
     *
     */
    public function setResponse(Response $response): void
    {
        $this->response = $response;

        /** @var Event $this */
        $this->stopPropagation();
    }

    /**
     * Returns whether a response was set.
     *
     * @return bool Whether a response was set
     */
    public function hasResponse(): bool
    {
        return isset($this->response);
    }
}
