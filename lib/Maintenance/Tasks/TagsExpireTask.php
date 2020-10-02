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

namespace Pimcore\Maintenance\Tasks;

use Pimcore\Maintenance\TaskInterface;
use Pimcore\Model\Tool\Tag\Config;
use Psr\Log\LoggerInterface;

/**
 * @deprecated
 */
final class TagsExpireTask implements TaskInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $currentTime = new \Carbon\Carbon();
        $tags = new Config\Listing();

        foreach ($tags->load() as $tag) {
            foreach ($tag->getItems() as $itemKey => $item) {
                try {
                    if ($item['date'] && $currentTime->getTimestamp() > $item['date']) {
                        //disable tag item if expired
                        $tag->items[$itemKey]['disabled'] = true;
                        $tag->save();
                    }
                } catch (\Exception $e) {
                    $this->logger->debug('Unable to process tag' . $tag->name . ', reason: '.$e->getMessage());
                }
            }
        }
    }
}
