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

namespace Pimcore\Migrations;

use Doctrine\Common\EventManager;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\EventDispatcher;
use Doctrine\Migrations\Events;

/**
 * Builds the event dispatcher of doctrine/migrations. With DBAL 4 and a connection (no entity manager) doctrine/migrations
 * dispatches its events to an event manager nobody can register listeners on, so Pimcore provides its own.
 *
 * @internal
 */
final class EventDispatcherFactory
{
    public function __construct(private readonly ImplicitCommitListener $implicitCommitListener)
    {
    }

    public function __invoke(DependencyFactory $dependencyFactory): EventDispatcher
    {
        $eventManager = new EventManager();
        $eventManager->addEventListener(Events::onMigrationsVersionExecuted, $this->implicitCommitListener);

        return new EventDispatcher($dependencyFactory->getConnection(), $eventManager);
    }
}
