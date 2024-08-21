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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Exception;
use Pimcore\Model;
use Pimcore\Model\DataObject\ClassDefinition\Service;
use Pimcore\Tool;

class Languagemultiselect extends Model\DataObject\ClassDefinition\Data\Multiselect
{
    /**
     * @internal
     */
    public bool $onlySystemLanguages = false;

    /**
     * @internal
     *
     * @throws Exception
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
    public function setOnlySystemLanguages(bool|int|null $value): static
    {
        $this->onlySystemLanguages = (bool) $value;

        return $this;
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
        return 'languagemultiselect';
    }
}
