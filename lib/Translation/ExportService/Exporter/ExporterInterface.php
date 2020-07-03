<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Translation\ExportService\Exporter;

use Pimcore\Translation\AttributeSet\AttributeSet;

interface ExporterInterface
{
    /**
     * @param AttributeSet $attributeSet
     * @param string|null $exportId
     *
     * @return string
     */
    public function export(AttributeSet $attributeSet, string $exportId = null): string;

    /**
     * @param string $exportId
     *
     * @return string
     */
    public function getExportFilePath(string $exportId): string;

    /**
     * @return string
     */
    public function getContentType(): string;
}
