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

namespace Pimcore\Model\Asset\MetaData\ClassDefinition\Data;

use Pimcore\Model\DataObject\Traits\SimpleNormalizerTrait;
use Pimcore\Normalizer\NormalizerInterface;

abstract class Data implements DataDefinitionInterface, NormalizerInterface
{
    use SimpleNormalizerTrait;

    public function __toString(): string
    {
        return get_class($this);
    }

    public function transformGetterData(mixed $data, array $params = []): mixed
    {
        return $data;
    }

    public function transformSetterData(mixed $data, array $params = []): mixed
    {
        return $data;
    }

    public function getDataFromEditMode(mixed $data, array $params = []): mixed
    {
        return $data;
    }

    public function getDataForResource(mixed $data, array $params = []): mixed
    {
        return $data;
    }

    public function getDataFromResource(mixed $data, array $params = []): mixed
    {
        return $data;
    }

    public function getDataForEditMode(mixed $data, array $params = []): mixed
    {
        return $data;
    }

    public function isEmpty(mixed $data, array $params = []): bool
    {
        return empty($data);
    }

    public function checkValidity(mixed $data, array $params = []): void
    {
    }

    public function getDataForListfolderGrid(mixed $data, array $params = []): mixed
    {
        return $data;
    }

    public function getDataFromListfolderGrid(mixed $data, array $params = []): mixed
    {
        return $data;
    }

    public function resolveDependencies(mixed $data, array $params = []): array
    {
        return [];
    }

    public function getVersionPreview(mixed $value, array $params = []): string
    {
        return (string)$value;
    }

    public function getDataForSearchIndex(mixed $data, array $params = []): ?string
    {
        if (is_scalar($data)) {
            return $params['name'] . ':' . $data;
        }

        return null;
    }
}
