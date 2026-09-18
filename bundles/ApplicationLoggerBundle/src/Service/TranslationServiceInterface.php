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

namespace Pimcore\Bundle\ApplicationLoggerBundle\Service;

/**
 * @deprecated since Pimcore 2026.3, will be removed in Pimcore 2027.0. It only served the removed
 *             legacy admin log controller and translates into the legacy "admin" domain.
 */
interface TranslationServiceInterface
{
    public function getTranslatedLogLevels(): array;

    public function getTranslatedLogLevel(int $key): string;
}
