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

use Pimcore\Targeting\Debug\Util\OverrideAttributeResolver;
use Pimcore\Targeting\OverrideHandlerInterface;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

class LanguageOverrideHandler implements OverrideHandlerInterface
{
    public function buildOverrideForm(FormBuilderInterface $form, Request $request)
    {
        $form->add('language', LanguageType::class, [
            'required' => false,
        ]);
    }

    public function overrideFromRequest(array $overrides, Request $request)
    {
        $language = $overrides['language'] ?? null;
        if (empty($language)) {
            return;
        }

        OverrideAttributeResolver::setOverrideValue($request, 'language', $language);
    }
}
