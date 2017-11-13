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
use Pimcore\Targeting\Model\VisitorInfo;

class DelegatingActionHandler implements ActionHandlerInterface
{
    /**
     * @var ActionHandlerLocatorInterface
     */
    private $actionHandlers;

    public function __construct(ActionHandlerLocatorInterface $actionHandlers)
    {
        $this->actionHandlers = $actionHandlers;
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
        $actionHandler->apply($visitorInfo, $action, $rule);
    }
}
