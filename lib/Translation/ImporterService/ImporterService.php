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

class ImporterService implements ImporterServiceInterface
{
    /**
     * @var ImporterInterface[]
     */
    private $importers = [];

    /**
     * @inheritdoc
     */
    public function import(AttributeSet $attributeSet, bool $saveElement = true)
    {
        $this->getImporter($attributeSet->getTranslationItem()->getType())->import($attributeSet, $saveElement);
    }

    public function registerImporter(string $type, ImporterInterface $importer): ImporterServiceInterface
    {
        $this->importers[$type] = $importer;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getImporter(string $type): ImporterInterface
    {
        if (isset($this->importers[$type])) {
            return $this->importers[$type];
        }

        throw new \Exception(sprintf('no importer for type "%s" registered', $type));
    }
}
