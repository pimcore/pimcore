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

use Pimcore\Messenger\ElementDependenciesMessage;
use Pimcore\Helper\LongRunningHelper;
use Pimcore\Model\Dependency;
use Pimcore\Model\Element\Service;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
class ElementDependenciesHandler
{
    public function __construct(protected LoggerInterface $logger, protected LongRunningHelper $longRunningHelper)
    {
    }

    public function __invoke(ElementDependenciesMessage $message): void
    {
        $this->logger->debug(sprintf('Processing dependencies of %s with ID %s ', $message->getType(), $message->getId()));

        $this->saveDependencies($message->getType(), $message->getId());
    }


    private function saveDependencies(string $type, int $id): void
    {
        $d = new Dependency();
        $d->setSourceType($type);
        $d->setSourceId($id);

        $element = Service::getElementById($type, $id);

        foreach ($element->resolveDependencies() as $requirement) {
            if ($requirement['id'] == $id && $requirement['type'] == $type) {
                // dont't add a reference to yourself
                continue;
            }

            $d->addRequirement($requirement['id'], $requirement['type']);
        }
        $d->save();
    }
}
