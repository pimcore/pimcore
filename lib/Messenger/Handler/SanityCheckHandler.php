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

namespace Pimcore\Messenger\Handler;

use Doctrine\DBAL\ArrayParameterType;
use Exception;
use Pimcore\Db;
use Pimcore\Messenger\SanityCheckMessage;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Symfony\Component\Messenger\Handler\Acknowledger;
use Symfony\Component\Messenger\Handler\BatchHandlerInterface;
use Symfony\Component\Messenger\Handler\BatchHandlerTrait;
use Throwable;

/**
 * @internal
 */
class SanityCheckHandler implements BatchHandlerInterface
{
    use BatchHandlerTrait;
    use HandlerHelperTrait;

    public function __invoke(SanityCheckMessage $message, ?Acknowledger $ack = null): mixed
    {
        return $this->handle($message, $ack);
    }

    // @phpstan-ignore-next-line
    private function process(array $jobs): void
    {
        $jobs = $this->filterUnique($jobs, static function (SanityCheckMessage $message) {
            return $message->getType() . '-' . $message->getId();
        });

        foreach ($jobs as [$message, $ack]) {
            try {
                $element = Service::getElementById($message->getType(), $message->getId(), ['force' => true]);
                if ($element) {
                    $this->performSanityCheck($element);
                }

                $ack->ack($message);
            } catch (Throwable $e) {
                $ack->nack($e);
            }
        }
    }

    /**
     * @throws Exception
     */
    private function performSanityCheck(ElementInterface $element): void
    {
        if (!$element instanceof PageSnippet && !$element instanceof Concrete && !$element instanceof Asset) {
            return;
        }

        // the element or its parent may have been deleted since the message was queued (bulk deletions
        // delete the related elements one after another); saving would re-insert the row as an orphan
        if (!$this->isStillPresent($element)) {
            return;
        }

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

    /**
     * Whether the element and its parent still exist in the database (a deleted element would be
     * re-inserted by save(), see Helper::updateOrInsert()).
     */
    private function isStillPresent(ElementInterface $element): bool
    {
        $table = match (true) {
            $element instanceof Asset => 'assets',
            $element instanceof PageSnippet => 'documents',
            default => 'objects',
        };

        $ids = array_unique(array_filter([$element->getId(), $element->getParentId()]));
        $found = Db::get()->fetchFirstColumn(
            'SELECT id FROM ' . $table . ' WHERE id IN (?)',
            [$ids],
            [ArrayParameterType::INTEGER]
        );

        return count($found) === count($ids);
    }
}
