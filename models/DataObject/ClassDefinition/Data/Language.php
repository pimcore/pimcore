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

use Pimcore;
use Pimcore\Model;
use Pimcore\Model\DataObject\ClassDefinition\Service;
use Pimcore\Tool;

class Language extends Model\DataObject\ClassDefinition\Data\Select
{
    /**
     * @internal
     */
    public bool $onlySystemLanguages = false;

    /**
     * @internal
     */
    public function configureOptions(): void
    {
        $validLanguages = Tool::getValidLanguages();
        $locales = Tool::getSupportedLocales();
        $options = [];

        foreach ($locales as $short => $translation) {
            if ($this->getOnlySystemLanguages()) {
                if (!in_array($short, $validLanguages)) {
                    continue;
                }
            }

            $options[] = [
                'key' => $translation,
                'value' => $short,
            ];
        }

        $this->setOptions($options);
    }

    public function getOnlySystemLanguages(): bool
    {
        return $this->onlySystemLanguages;
    }

    /**
     * @return $this
     */
    public function setOnlySystemLanguages(bool|int $value): static
    {
        $this->onlySystemLanguages = (bool) $value;

        return $this;
    }

    public static function __set_state(array $data): static
    {
        $obj = parent::__set_state($data);

        if (Pimcore::inAdmin()) {
            $obj->configureOptions();
        }

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

    public function isFilterable(): bool
    {
        return true;
    }

    public function getFieldType(): string
    {
        return 'language';
    }
}
