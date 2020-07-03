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

use Pimcore\Targeting\Debug\Form\LocationType;
use Pimcore\Targeting\Debug\Util\OverrideAttributeResolver;
use Pimcore\Targeting\OverrideHandlerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

class LocationOverrideHandler implements OverrideHandlerInterface
{
    public function buildOverrideForm(FormBuilderInterface $form, Request $request)
    {
        $form->add('location', LocationType::class, [
            'label' => 'Location',
            'required' => false,
            'attr' => [
                'class' => '_ptgtb__override-form__collapse-section',
            ],
        ]);
    }

    public function overrideFromRequest(array $overrides, Request $request)
    {
        $location = $overrides['location'] ?? [];
        if (empty($location)) {
            return;
        }

        OverrideAttributeResolver::setOverrideValue($request, 'location', $location);
    }
}
