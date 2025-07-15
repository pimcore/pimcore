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

class ImporterService implements ImporterServiceInterface
{
    /**
     * @var ImporterInterface[]
     */
    private array $importers = [];

    public function import(AttributeSet $attributeSet, bool $saveElement = true): void
    {
        $this->getImporter($attributeSet->getTranslationItem()->getType())->import($attributeSet, $saveElement);
    }

    public function registerImporter(string $type, ImporterInterface $importer): ImporterServiceInterface
    {
        $this->importers[$type] = $importer;

        return $this;
    }

    public function getImporter(string $type): ImporterInterface
    {
        if (isset($this->importers[$type])) {
            return $this->importers[$type];
        }

        throw new Exception(sprintf('no importer for type "%s" registered', $type));
    }
}
