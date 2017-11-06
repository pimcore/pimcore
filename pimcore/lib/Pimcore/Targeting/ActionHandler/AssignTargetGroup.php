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

use Pimcore\Model\Tool\Targeting\Persona;
use Pimcore\Model\Tool\Targeting\Rule;
use Pimcore\Targeting\Model\VisitorInfo;
use Pimcore\Targeting\Session\SessionConfigurator;
use Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag;

class AssignTargetGroup implements ActionHandlerInterface
{
    public function apply(VisitorInfo $visitorInfo, Rule\Actions $actions, Rule $rule)
    {
        if (!$actions->getPersonaEnabled() || empty($actions->getPersonaId())) {
            return;
        }

        $persona = Persona::getById($actions->getPersonaId());

        if (!$persona || !$persona->getActive()) {
            return;
        }

        $assign    = true;
        $threshold = (int)$persona->getThreshold();

        if ($threshold > 1) {
            // only check session entries if threshold was configured
            $assign = $this->checkThresholdAssigment($visitorInfo, $persona, $threshold);
        }

        if ($assign) {
            $visitorInfo->addPersona($persona);
        }
    }

    private function checkThresholdAssigment(VisitorInfo $visitorInfo, Persona $persona, int $threshold): bool
    {
        $request = $visitorInfo->getRequest();
        if (!$request->getSession()) {
            return false;
        }

        $session = $request->getSession();

        /** @var NamespacedAttributeBag $bag */
        $bag = $session->getBag(SessionConfigurator::TARGETING_BAG);

        $data = $bag->get('assign_target_group', []);

        $assignments = $data[$persona->getId()] ?? 0;
        $assignments++;

        $data[$persona->getId()] = $assignments;
        $bag->set('assign_target_group', $data);

        $session->save();

        // check amount after assigning - this means that with
        // a threshold of 3 the target group will be assigned on and
        // after the third matching request
        return $assignments >= $threshold;
    }
}
