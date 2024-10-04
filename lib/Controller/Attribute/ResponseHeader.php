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

namespace Pimcore\Controller\Attribute;

use Attribute;

/**
 * Allows to set HTTP headers on the response via attributes. The attribute will
 * be processed by ResponseHeaderListener which will set the HTTP headers on the
 * response.
 *
 * See ResponseHeaderBag for documentation on the fields.
 *
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION)]
final class ResponseHeader
{
    public function __construct(
        protected string $key,
        protected string|array $values = '',
        protected bool $replace = false
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getValues(): array|string
    {
        return $this->values;
    }

    public function setValues(array|string $values): void
    {
        $this->values = $values;
    }

    public function getReplace(): bool
    {
        return $this->replace;
    }

    public function setReplace(bool $replace): void
    {
        $this->replace = $replace;
    }
}
