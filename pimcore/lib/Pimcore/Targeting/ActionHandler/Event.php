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
use Pimcore\Targeting\Model\VisitorInfo;
use Pimcore\Targeting\Session\SessionConfigurator;
use Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Event implements ActionHandlerInterface
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @inheritDoc
     */
    public function apply(VisitorInfo $visitorInfo, Rule\Actions $actions, Rule $rule)
    {
        if (!$actions->getEventEnabled() || empty($actions->getEventKey())) {
            return;
        }

        /** @var NamespacedAttributeBag $bag */
        $bag = $this->session->getBag(SessionConfigurator::TARGETING_BAG);

        $events = $bag->get('events', []);
        $events[] = [
            'key'   => $actions->getEventKey(),
            'value' => $actions->getEventValue(),
            'date'  => new \DateTimeImmutable()
        ];

        $bag->set('events', $events);

        $this->session->save();
    }
}
