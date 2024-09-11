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

namespace Pimcore\Bundle\SimpleBackendSearchBundle\MessageHandler;

use Pimcore\Bundle\SimpleBackendSearchBundle\Message\SearchBackendMessage;
use Pimcore\Bundle\SimpleBackendSearchBundle\Model\Search\Backend\Data;
use Pimcore\Messenger\Handler\HandlerHelperTrait;
use Pimcore\Model\Element;
use Symfony\Component\Messenger\Handler\Acknowledger;
use Symfony\Component\Messenger\Handler\BatchHandlerInterface;
use Symfony\Component\Messenger\Handler\BatchHandlerTrait;
use Throwable;

/**
 * @internal
 */
class SearchBackendHandler implements BatchHandlerInterface
{
    use BatchHandlerTrait;
    use HandlerHelperTrait;

    public function __invoke(SearchBackendMessage $message, Acknowledger $ack = null): mixed
    {
        return $this->handle($message, $ack);
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

                $searchEntry = Data::getForElement($element);
                if ($searchEntry instanceof Data && $searchEntry->getId() instanceof Data\Id) {
                    $searchEntry->setDataFromElement($element);
                    $searchEntry->save();
                } else {
                    $searchEntry = new Data($element);
                    $searchEntry->save();
                }

                $ack->ack($message);
            } catch (Throwable $e) {
                $ack->nack($e);
            }
        }
    }

    private function shouldFlush(): bool
    {
        return 50 <= count($this->jobs);
    }
}
