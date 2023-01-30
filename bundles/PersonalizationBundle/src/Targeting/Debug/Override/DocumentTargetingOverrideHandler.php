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

use Pimcore\Bundle\PersonalizationBundle\Model\Tool\Targeting\TargetGroup;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Document\DocumentTargetingConfigurator;
use Pimcore\Bundle\PersonalizationBundle\Targeting\OverrideHandlerInterface;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

class DocumentTargetingOverrideHandler implements OverrideHandlerInterface
{
    private DocumentTargetingConfigurator $documentTargetingConfigurator;

    public function __construct(DocumentTargetingConfigurator $documentTargetingConfigurator)
    {
        $this->documentTargetingConfigurator = $documentTargetingConfigurator;
    }

    public function buildOverrideForm(FormBuilderInterface $form, Request $request): void
    {
        $form->add('documentTargetGroup', ChoiceType::class, [
            'label' => 'Document Target Group',
            'required' => false,
            'choice_loader' => new CallbackChoiceLoader(function () {
                return (new TargetGroup\Listing())->load();
            }),
            'choice_value' => function (TargetGroup $targetGroup = null) {
                return $targetGroup ? $targetGroup->getId() : '';
            },
            'choice_label' => function (TargetGroup $targetGroup = null, $key, $index) {
                return $targetGroup ? $targetGroup->getName() : '';
            },
        ]);
    }

    public function overrideFromRequest(array $overrides, Request $request): void
    {
        $targetGroup = $overrides['documentTargetGroup'] ?? null;
        if ($targetGroup && $targetGroup instanceof TargetGroup) {
            $this->documentTargetingConfigurator->setOverrideTargetGroup($targetGroup);
        }
    }
}
