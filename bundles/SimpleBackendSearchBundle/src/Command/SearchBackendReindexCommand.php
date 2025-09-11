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

namespace Pimcore\Bundle\SimpleBackendSearchBundle\Command;

use Exception;
use Pimcore;
use Pimcore\Console\AbstractCommand;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Version;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:search-backend-reindex',
    description: 'Re-indexes the backend search of pimcore',
    aliases: ['search-backend-reindex']
)]
class SearchBackendReindexCommand extends AbstractCommand
{
    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // clear all data
        $db = \Pimcore\Db::get();

        $types = ['asset', 'document', 'object'];

        foreach ($types as $type) {
            $elementIds = $db->fetchFirstColumn(
                'WITH RECURSIVE elements AS (
                    SELECT current.id, current.modificationDate, current.parentId
                    FROM `'.$type.'s` current
                    WHERE id=1
                    UNION ALL
                    SELECT
                        descendants.id,
                        GREATEST(elements.modificationDate, descendants.modificationDate) AS modificationDate,
                        descendants.parentId
                    FROM `'.$type.'s` descendants
                    INNER JOIN elements ON descendants.parentId = elements.id
                )
                SELECT elements.id
                FROM elements
                LEFT JOIN search_backend_data ON elements.id=search_backend_data.id
                    AND search_backend_data.mainType=\''.$type.'\'
                WHERE search_backend_data.id IS NULL
                    OR search_backend_data.modificationDate < elements.modificationDate'
            );
            $elementsTotal = count($elementIds);

            foreach ($elementIds as $i => $elementId) {
                if ($i % $elementsPerLoop === 0) {
                    Pimcore::collectGarbage();
                    Logger::info('Processing '.$type.': '.min($i + $elementsPerLoop, count($elementIds)).'/'.$elementsTotal);
                }

                try {
                    $element = Service::getElementById($type, $elementId);
                    if (!$element instanceof Pimcore\Model\Element\ElementInterface) {
                        continue;
                    }

                    //process page count, if not exists
                    if (
                        $element instanceof Asset\Document &&
                        !$element->getCustomSetting('document_page_count') &&
                        $element->processPageCount()
                    ) {
                        $this->saveAsset($element);
                    }

                    $searchEntry = new Pimcore\Bundle\SimpleBackendSearchBundle\Model\Search\Backend\Data();
                    $searchEntry->setDataFromElement($element);
                    $searchEntry->save();
                } catch (Throwable $e) {
                    Logger::err((string)$e);
                }
            }
        }

        $db->executeQuery('OPTIMIZE TABLE search_backend_data;');

        return 0;
    }

    /**
     * @throws Exception
     */
    private function saveAsset(Asset $asset): void
    {
        Version::disable();
        $asset->markFieldDirty('modificationDate'); // prevent modificationDate from being changed
        $asset->save();
        Version::enable();
    }
}
