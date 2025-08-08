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

namespace Pimcore\Bundle\XliffBundle\ImportDataExtractor;

use Exception;
use Pimcore\Bundle\XliffBundle\AttributeSet\AttributeSet;

interface ImportDataExtractorInterface
{
    /**
     *
     *
     * @throws Exception
     */
    public function extractElement(string $importId, int $stepId): ?AttributeSet;

    public function getImportFilePath(string $importId): string;

    /**
     *
     *
     * @throws Exception
     */
    public function countSteps(string $importId): int;
}
