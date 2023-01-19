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

namespace Pimcore\Bundle\PersonalizationBundle\Targeting\Debug\Override;

use Pimcore\Bundle\PersonalizationBundle\Targeting\Debug\Form\DeviceType;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Debug\Util\OverrideAttributeResolver;
use Pimcore\Bundle\PersonalizationBundle\Targeting\OverrideHandlerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

class DeviceOverrideHandler implements OverrideHandlerInterface
{
    public function buildOverrideForm(FormBuilderInterface $form, Request $request): void
    {
        $form->add('device', DeviceType::class, [
            'label' => 'Device',
            'required' => false,
            'attr' => [
                'class' => '_ptgtb__override-form__collapse-section',
            ],
        ]);
    }

    public function overrideFromRequest(array $overrides, Request $request): void
    {
        $device = $overrides['device'] ?? [];
        if (empty($device)) {
            return;
        }

        OverrideAttributeResolver::setOverrideValue($request, 'device', $device);
    }
}
