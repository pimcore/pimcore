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

namespace Pimcore\Model\DataObject\ClassDefinition\Layout;

use Pimcore\Model\DataObject\Concrete;

interface DynamicTextLabelInterface
{
    /**
     * @param string $data as provided in the class definition
     * @param array<string, mixed> $params
     */
    public function renderLayoutText(string $data, ?Concrete $object, array $params): string;
}
