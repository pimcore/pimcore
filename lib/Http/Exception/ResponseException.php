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

namespace Pimcore\Http\Exception;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class ResponseException extends Exception
{
    protected Response $response;

    public function __construct(Response $response, Exception $previous = null)
    {
        parent::__construct('', 0, $previous);

        $this->response = $response;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}
