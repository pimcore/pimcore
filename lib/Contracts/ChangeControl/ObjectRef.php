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

namespace Pimcore\Contracts\ChangeControl;

use InvalidArgumentException;

/**
 * A data object on a branch: an existing one by its id, a new one by its token, and one the merge
 * created by both, so a consumer can tell which of its new objects got which id.
 */
final readonly class ObjectRef
{
    private function __construct(
        public ?int $id,
        public ?string $token,
    ) {
    }

    public static function of(int $id): self
    {
        return new self(self::id($id), null);
    }

    public static function token(string $token): self
    {
        return new self(null, self::validToken($token));
    }

    public static function created(string $token, int $id): self
    {
        return new self(self::id($id), self::validToken($token));
    }

    private static function id(int $id): int
    {
        if ($id < 1) {
            throw new InvalidArgumentException(sprintf('A data object has a positive id, not %d.', $id));
        }

        return $id;
    }

    private static function validToken(string $token): string
    {
        if (preg_match('/^[A-Za-z0-9._-]{1,80}$/D', $token) !== 1) {
            throw new InvalidArgumentException(
                sprintf('"%s" is no token: use letters, digits, ".", "_" and "-", at most 80.', $token)
            );
        }

        return $token;
    }
}
