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

namespace Pimcore\Model\DataObject\ClassDefinition;

use Pimcore\Model\DataObject\Concrete;

interface PreviewGeneratorInterface
{
    public const PARAMETER_SITE = 'site';

    public const PARAMETER_LOCALE = 'locale';

    public function generatePreviewUrl(Concrete $object, array $params): string;

    /**
     * @return list<array{name: string, label: string, value: array<string, string>, defaultValue: int|string|null}>
     */
    public function getPreviewConfig(Concrete $object): array;
}
