<?php

declare(strict_types=1);

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

namespace Pimcore\Targeting\ActionHandler;

use Pimcore\Model\Tool\Targeting\Rule;
use Pimcore\Targeting\ActionHandlerLocatorInterface;
use Pimcore\Targeting\DataLoaderInterface;
use Pimcore\Targeting\DataProviderDependentInterface;
use Pimcore\Targeting\Model\VisitorInfo;

class DelegatingActionHandler implements ActionHandlerInterface
{
    /**
     * @var ActionHandlerLocatorInterface
     */
    private $actionHandlers;

    /**
     * @var DataLoaderInterface
     */
    private $dataLoader;

    public function __construct(
        ActionHandlerLocatorInterface $actionHandlers,
        DataLoaderInterface $dataLoader
    )
    {
        $this->actionHandlers = $actionHandlers;
        $this->dataLoader     = $dataLoader;
    }

    /**
     * @inheritDoc
     */
    public function apply(VisitorInfo $visitorInfo, array $action, Rule $rule = null)
    {
        /** @var string $type */
        $type = $action['type'] ?? null;

        if (empty($type)) {
            throw new \InvalidArgumentException('Invalid action: Type is not set');
        }

        if (!$this->actionHandlers->has($type)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid condition: there is no action handler registered for type "%s"',
                $type
            ));
        }

        $actionHandler = $this->actionHandlers->get($type);

        // load data providers if necessary
        if ($actionHandler instanceof DataProviderDependentInterface) {
            $this->dataLoader->loadDataFromProviders($visitorInfo, $actionHandler->getDataProviderKeys());
        }

        $actionHandler->apply($visitorInfo, $action, $rule);
    }
}
