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

/**
 * @see OptionsProviderInterface
 */
trait OptionsProviderTrait
{
    public ?string $optionsProviderType = null;

    public ?string $optionsProviderClass = null;

    public ?string $optionsProviderData = null;

    public function getOptionsProviderType(): ?string
    {
        return $this->optionsProviderType;
    }

    public function setOptionsProviderType(?string $optionsProviderType): void
    {
        $this->optionsProviderType = $optionsProviderType;
    }

    public function getOptionsProviderClass(): ?string
    {
        return $this->optionsProviderClass;
    }

    public function setOptionsProviderClass(?string $optionsProviderClass): void
    {
        $this->optionsProviderClass = $optionsProviderClass;
    }

    public function getOptionsProviderData(): ?string
    {
        return $this->optionsProviderData;
    }

    public function setOptionsProviderData(?string $optionsProviderData): void
    {
        $this->optionsProviderData = $optionsProviderData;
    }

    public function useConfiguredOptions(): bool
    {
        return $this->getOptionsProviderType() === OptionsProviderInterface::TYPE_CONFIGURE
            // Legacy fallback in case no type was set yet and no class/service was configured
            || ($this->getOptionsProviderType() === null && empty($this->getOptionsProviderClass()));
    }
}
