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
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Document;
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
    use HandlerHelperTrait;

    public function __invoke(SearchBackendMessage $message, Acknowledger $ack = null)
    {
        return $this->handle($message, $ack);
    }

    private function processElement(Element\ElementInterface $element, bool $updateChildren) {

        $searchEntry = Data::getForElement($element);
        if ($searchEntry->getId() instanceof Data\Id) {
            $searchEntry->setDataFromElement($element);
        } else {
            $searchEntry = new Data($element);
        }
        $searchEntry->save();

        if ($updateChildren) {
            foreach ($element->getChildren() as $child) {
                if ($child instanceof Asset || $child instanceof AbstractObject || $child instanceof Document) {
                    $data = Data::getForElement($child);

                    $shouldChildrenBeUpdated = $child->getRealFullPath() == $data->getFullPath();
                    $this->processElement($child, $shouldChildrenBeUpdated);
                }
            }
        }
    }

    private function process(array $jobs): void
    {
        $jobs = $this->filterUnique($jobs, static function (SearchBackendMessage $message) {
            return $message->getType() . '-' . $message->getId();
        });

        foreach ($jobs as [$message, $ack]) {
            try {
                $element = Element\Service::getElementById($message->getType(), $message->getId());
                if (!$element instanceof Element\ElementInterface) {
                    $ack->ack($message);

                    continue;
                }

                $this->processElement($element, $message->shouldChildrenBeUpdated());
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
