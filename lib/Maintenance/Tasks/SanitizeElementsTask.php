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
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Sanitycheck;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Version;
use Psr\Log\LoggerInterface;

final class SanitizeElementsTask implements TaskInterface
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
        $sanityCheck = Sanitycheck::getNext();
        $count = 0;
        while ($sanityCheck) {
            $count++;
            if ($count % 10 == 0) {
                \Pimcore::collectGarbage();
            }

            $element = Service::getElementById($sanityCheck->getType(), $sanityCheck->getId(), true);
            if ($element) {
                try {
                    $this->performSanityCheck($element);
                } catch (\Exception $e) {
                    $this->logger->error('Element\\Service: sanity check for element with id [ ' . $element->getId() . ' ] and type [ ' . Service::getType($element) . ' ] failed');
                }
                $sanityCheck->delete();
            } else {
                $sanityCheck->delete();
            }
            $sanityCheck = Sanitycheck::getNext();

            // reduce load on server
            $this->logger->debug('Now timeout for 3 seconds');
            sleep(3);
        }
    }

    /**
     * @param PageSnippet|Asset|Concrete $element
     *
     * @throws \Exception
     */
    protected function performSanityCheck(ElementInterface $element)
    {
        $latestNotPublishedVersion = null;

        if ($latestVersion = $element->getLatestVersion()) {
            if ($latestVersion->getDate() > $element->getModificationDate() || $latestVersion->getVersionCount() > $element->getVersionCount()) {
                $latestNotPublishedVersion = $latestVersion;
            }
        }

        $element->setUserModification(0);
        $element->save(['versionNote' => 'Sanity Check']);

        if ($latestNotPublishedVersion) {
            // we have to make sure that the previous unpublished version is on top of the list again
            // otherwise we will get wrong data in editmode
            $latestNotPublishedVersionCount = $element->getVersionCount() + 1;
            $latestNotPublishedVersion->setVersionCount($latestNotPublishedVersionCount);
            $latestNotPublishedVersion->setNote('Sanity Check');
            $latestNotPublishedVersion->save();
        }
    }
}
