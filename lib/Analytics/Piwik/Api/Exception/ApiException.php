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

namespace Pimcore\Analytics\Piwik\Api\Exception;

use Throwable;

/**
 * @deprecated
 */
class ApiException extends \RuntimeException
{
    /**
     * @var array|null
     */
    private $response;

    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        $message = 'Piwik API request failed: ' . $message;

        parent::__construct($message, $code, $previous);
    }

    public static function fromResponse(string $message, array $response = null): self
    {
        $ex = new self($message);
        $ex->response = $response;

        return $ex;
    }

    /**
     * @return array|null
     */
    public function getResponse()
    {
        return $this->response;
    }
}
