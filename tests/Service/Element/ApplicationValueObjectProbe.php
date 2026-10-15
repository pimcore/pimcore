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

/**
 * Stands in for an application value object that lives outside the `Pimcore\` namespace and ends up
 * inside an element (e.g. returned by a custom `marshalVersion()` implementation).
 * Deliberately declared with no namespace and not autoloaded via PSR-4, like `UntrustedClassProbe`.
 */
class ApplicationValueObjectProbe
{
    public string $value = 'application';
}
