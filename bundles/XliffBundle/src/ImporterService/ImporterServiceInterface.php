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

namespace Pimcore\Bundle\XliffBundle\ImporterService;

use Exception;
use Pimcore\Bundle\XliffBundle\AttributeSet\AttributeSet;
use Pimcore\Bundle\XliffBundle\ImporterService\Importer\ImporterInterface;

interface ImporterServiceInterface
{
    /**
     *
     *
     * @throws Exception
     */
    public function import(AttributeSet $attributeSet, bool $saveElement = true): void;

    public function registerImporter(string $type, ImporterInterface $importer): ImporterServiceInterface;

    /**
     *
     *
     * @throws Exception
     */
    public function getImporter(string $type): ImporterInterface;
}
