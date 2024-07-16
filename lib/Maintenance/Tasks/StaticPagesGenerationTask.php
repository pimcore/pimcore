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

namespace Pimcore\Maintenance\Tasks;

use Exception;
use Pimcore;
use Pimcore\Document\StaticPageGenerator;
use Pimcore\Maintenance\TaskInterface;
use Pimcore\Model\Document;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class StaticPagesGenerationTask implements TaskInterface
{
    private StaticPageGenerator $generator;

    private LoggerInterface $logger;

    public function __construct(StaticPageGenerator $generator, LoggerInterface $logger)
    {
        $this->generator = $generator;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        $listing = new Document\Listing();
        $listing->setCondition("`type` = 'page'");
        $listing->setOrderKey('id');
        $listing->setOrder('DESC');

        $total = $listing->getTotalCount();
        $perLoop = 10;

        for ($i = 0; $i < (ceil($total / $perLoop)); $i++) {
            $listing->setLimit($perLoop);
            $listing->setOffset($i * $perLoop);

            /** @var Document\Page[] $pages */
            $pages = $listing->load();
            foreach ($pages as $page) {
                if ($page->getStaticGeneratorEnabled()) {
                    try {
                        $lastModified = $this->generator->getLastModified($page);
                        $generate = true;
                        if ($staticLifetime = $page->getStaticGeneratorLifetime()) {
                            $currentTime = \Carbon\Carbon::now();
                            $currentTime->subMinutes($staticLifetime);

                            if ($lastModified > $currentTime->getTimestamp()) {
                                $generate = false;
                            }
                        }

                        if ($generate) {
                            $this->generator->generate($page, ['is_cli' => true]);
                        }
                    } catch (Exception $e) {
                        $this->logger->debug('Unable to generate Static Page for document ID:' . $page->getId() . ', reason: ' . $e->getMessage());
                    }
                }
            }
            Pimcore::collectGarbage();
        }
    }
}
