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

namespace Pimcore\Messenger\Handler;

use Exception;
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

    public function __invoke(SanityCheckMessage $message, Acknowledger $ack = null): mixed
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
