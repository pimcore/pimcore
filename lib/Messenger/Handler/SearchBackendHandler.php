<?php

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

use Pimcore\Messenger\SearchBackendMessage;
use Pimcore\Model\Element;
use Pimcore\Model\Search\Backend\Data;
use Symfony\Component\Messenger\Handler\Acknowledger;
use Symfony\Component\Messenger\Handler\BatchHandlerInterface;
use Symfony\Component\Messenger\Handler\BatchHandlerTrait;

/**
 * @internal
 */
class SearchBackendHandler implements BatchHandlerInterface
{
    use BatchHandlerTrait;

    public function __invoke(SearchBackendMessage $message, Acknowledger $ack = null)
    {
        return $this->handle($message, $ack);
    }

    private function process(array $jobs): void
    {
        foreach ($jobs as [$message, $ack]) {
            try {
                $element = Element\Service::getElementById($message->getType(), $message->getId());
                if (!$element instanceof Element\ElementInterface) {
                    return;
                }

                $searchEntry = Data::getForElement($element);
                if ($searchEntry instanceof Data && $searchEntry->getId() instanceof Data\Id) {
                    $searchEntry->setDataFromElement($element);
                    $searchEntry->save();
                } else {
                    $searchEntry = new Data($element);
                    $searchEntry->save();
                }

                $ack->ack($message);
            } catch (\Throwable $e) {
                $ack->nack($e);
            }
        }
    }

    private function shouldFlush(): bool
    {
        return 50 <= \count($this->jobs);
    }
}
