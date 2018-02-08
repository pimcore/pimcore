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

namespace Pimcore\Targeting\Debug\Override;

use Pimcore\Event\Targeting\OverrideEvent;
use Pimcore\Event\TargetingEvents;
use Pimcore\Targeting\Debug\Form\DeviceType;
use Pimcore\Targeting\Debug\Form\LocationType;
use Pimcore\Targeting\OverrideHandlerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

class DeviceOverrideHandler implements OverrideHandlerInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function buildOverrideForm(FormBuilderInterface $form, Request $request)
    {
        $form->add('device', DeviceType::class, [
            'label'    => 'Device',
            'required' => false,
            'attr'     => [
                'class' => '_ptgtb__override-form__collapse-section'
            ]
        ]);
    }

    public function overrideFromRequest(array $overrides, Request $request)
    {
        $device = $overrides['device'] ?? [];
        if (empty($device)) {
            return;
        }

        $type = 'device';

        $this->eventDispatcher->dispatch(
            TargetingEvents::overrideEventName($type),
            new OverrideEvent($type, $device)
        );
    }
}
