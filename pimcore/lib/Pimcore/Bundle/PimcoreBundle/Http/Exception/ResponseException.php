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

namespace Pimcore\Bundle\PimcoreBundle\Http\Exception;

use Symfony\Component\HttpFoundation\Response;

class ResponseException extends \Exception
{
    /**
     * @var Response
     */
    protected $response;

    /**
     * @param Response   $response
     * @param \Exception $previous
     */
    public function __construct(Response $response, \Exception $previous = null)
    {
        parent::__construct('', 0, $previous);

        $this->response = $response;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}
