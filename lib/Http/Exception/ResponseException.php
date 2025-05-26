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

namespace Pimcore\Http\Exception;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class ResponseException extends Exception
{
    protected Response $response;

    public function __construct(Response $response, ?Exception $previous = null)
    {
        parent::__construct('', 0, $previous);

        $this->response = $response;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}
