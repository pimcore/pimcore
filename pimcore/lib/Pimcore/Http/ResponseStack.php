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

namespace Pimcore\Http;

use Symfony\Component\HttpFoundation\Response;

/**
 * This stack can be used to collect responses to be sent from parts which cannot
 * directly influence the request-response cycle (e.g. templating parts). For example
 * this is used to read responses from an areabrick's action() method which is pushed
 * to this stack.
 *
 * The ResponseStackListener takes care of sending back the response set on this stack.
 */
class ResponseStack
{
    /**
     * @var Response[]
     */
    private $responses = [];

    public function push(Response $response)
    {
        $this->responses[] = $response;
    }

    public function hasResponses(): bool
    {
        return !empty($this->responses);
    }

    /**
     * @return Response[]
     */
    public function getResponses(): array
    {
        return $this->responses;
    }

    public function pop(): Response
    {
        if (empty($this->responses)) {
            throw new \UnderflowException('There are no responses on the stack.');
        }

        return array_pop($this->responses);
    }

    public function getLastResponse(): Response
    {
        if (empty($this->responses)) {
            throw new \UnderflowException('There are no responses on the stack.');
        }

        return end($this->responses);
    }
}
