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
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\CountryOptionsProvider;

class Countrymultiselect extends Model\DataObject\ClassDefinition\Data\Multiselect
{
    /**
     * Restrict selection to comma-separated list of countries.
     *
     * @internal
     *
     */
    public ?string $restrictTo = null;

    public function setRestrictTo(array|string|null $restrictTo): void
    {
        /**
         * @extjs6
         */
        if (is_array($restrictTo)) {
            $restrictTo = implode(',', $restrictTo);
        }

        $this->restrictTo = $restrictTo;
    }

    public function getRestrictTo(): ?string
    {
        return $this->restrictTo;
    }

    public function getOptionsProviderClass(): string
    {
        return '@' . CountryOptionsProvider::class;
    }

    public function getFieldType(): string
    {
        return 'countrymultiselect';
    }
}
