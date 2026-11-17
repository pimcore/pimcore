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

namespace Pimcore\Tests\Support\Model;

use Pimcore\Model\DataObject\PreGetValueHookInterface;
use Pimcore\Model\DataObject\Unittest;

/**
 * A unittest object whose getters return the values set with setHookedValue() instead of the stored ones, outside
 * of the admin like every pre get value hook.
 *
 * @internal
 */
final class HookedUnittest extends Unittest implements PreGetValueHookInterface
{
    /**
     * @var array<string, mixed>
     */
    private static array $hookedValues = [];

    public static function setHookedValue(string $key, mixed $value): void
    {
        self::$hookedValues[$key] = $value;
    }

    public static function resetHookedValues(): void
    {
        self::$hookedValues = [];
    }

    public function preGetValue(string $key): mixed
    {
        return self::$hookedValues[$key] ?? null;
    }

    /**
     * The stored object as an instance of this class, with no lazy loaded field loaded.
     */
    public static function fromStored(Unittest $stored): self
    {
        $object = new self();
        $object->setId($stored->getId());
        $object->setParentId($stored->getParentId());
        $object->setKey($stored->getKey());
        $object->setPublished($stored->getPublished());
        $object->setOmitMandatoryCheck(true);

        return $object;
    }
}
