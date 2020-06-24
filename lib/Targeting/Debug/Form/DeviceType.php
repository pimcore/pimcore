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

namespace Pimcore\Targeting\Debug\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class DeviceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('hardwarePlatform', ChoiceType::class, [
            'label' => 'Hardware Platform',
            'required' => false,
            'choices' => [
                'Desktop' => 'desktop',
                'Tablet' => 'tablet',
                'Mobile' => 'mobile',
            ],
        ]);

        $builder->add('operatingSystem', ChoiceType::class, [
            'label' => 'Operating System',
            'required' => false,
            'choices' => [
                'Windows' => 'windows',
                'Mac OS' => 'macos',
                'Linux' => 'linux',
                'Android' => 'android',
                'iOS' => 'ios',
            ],
        ]);

        $builder->add('browser', ChoiceType::class, [
            'label' => 'Browser',
            'required' => false,
            'choices' => [
                'Internet Explorer' => 'ie',
                'Firefox' => 'firefox',
                'Google Chrome' => 'chrome',
                'Safari' => 'safari',
                'Opera' => 'opera',
            ],
        ]);
    }
}
