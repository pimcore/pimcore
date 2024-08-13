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
