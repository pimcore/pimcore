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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\DataObject\ClassDefinition\Service;

class Gender extends Model\DataObject\ClassDefinition\Data\Select
{
    public function configureOptions(): void
    {
        $options = [
            ['key' => 'male', 'value' => 'male'],
            ['key' => 'female', 'value' => 'female'],
            ['key' => 'other', 'value' => 'other'],
            ['key' => 'unknown', 'value' => 'unknown'],
        ];

        $this->setOptions($options);
    }

    public static function __set_state(array $data): static
    {
        $obj = parent::__set_state($data);
        $obj->configureOptions();

        return $obj;
    }

    public function jsonSerialize(): mixed
    {
        if (Service::doRemoveDynamicOptions()) {
            $this->options = null;
        }

        return parent::jsonSerialize();
    }

    public function resolveBlockedVars(): array
    {
        $blockedVars = parent::resolveBlockedVars();
        $blockedVars[] = 'options';

        return $blockedVars;
    }

    public function getFieldType(): string
    {
        return 'gender';
    }
}
