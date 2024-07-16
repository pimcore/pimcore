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
