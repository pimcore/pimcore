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

namespace Pimcore\Translation\ImporterService;

use Pimcore\Translation\AttributeSet\AttributeSet;
use Pimcore\Translation\ImporterService\Importer\ImporterInterface;

interface ImporterServiceInterface
{
    /**
     * @param AttributeSet $attributeSet
     * @param bool $saveElement
     *
     * @return void
     *
     * @throws \Exception
     */
    public function import(AttributeSet $attributeSet, bool $saveElement = true);

    /**
     * @param string $type
     * @param ImporterInterface $importer
     *
     * @return ImporterServiceInterface
     */
    public function registerImporter(string $type, ImporterInterface $importer): ImporterServiceInterface;

    /**
     * @param string $type
     *
     * @return ImporterInterface
     *
     * @throws \Exception
     */
    public function getImporter(string $type): ImporterInterface;
}
