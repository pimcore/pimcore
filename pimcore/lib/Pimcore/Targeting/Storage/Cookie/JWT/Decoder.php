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

namespace Pimcore\Targeting\Storage\Cookie\JWT;

/**
 * Extends core decoder and decodes to array instead of object.
 */
class Decoder extends \Lcobucci\JWT\Parsing\Decoder
{
    /**
     * @inheritDoc
     */
    public function jsonDecode($json)
    {
        $data = json_decode($json, true);

        if (json_last_error() != JSON_ERROR_NONE) {
            throw new \RuntimeException('Error while decoding to JSON: ' . json_last_error_msg());
        }

        return $data;
    }
}
