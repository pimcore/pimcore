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

namespace Pimcore\Targeting\Debug;

use Pimcore\Targeting\OverrideHandlerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class OverrideHandler
{
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var iterable|OverrideHandlerInterface[]
     */
    private $overrideHandlers;

    /**
     * @param FormFactoryInterface $formFactory
     * @param iterable|OverrideHandlerInterface[] $overrideHandlers
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        $overrideHandlers
    ) {
        $this->formFactory = $formFactory;
        $this->overrideHandlers = $overrideHandlers;
    }

    public function getForm(Request $request): FormInterface
    {
        if ($request->attributes->has('pimcore_targeting_override_form')) {
            /** @var FormInterface $form */
            $form = $request->attributes->get('pimcore_targeting_override_form');

            return $form;
        }

        $form = $this->buildForm($request);

        $request->attributes->set('pimcore_targeting_override_form', $form);

        return $form;
    }

    protected function buildForm(Request $request): FormInterface
    {
        $formBuilder = $this->formFactory->createNamedBuilder('_ptg_overrides', FormType::class, null, [
            'csrf_protection' => false,
        ]);

        $formBuilder->setMethod('GET');

        foreach ($this->overrideHandlers as $handler) {
            $handler->buildOverrideForm($formBuilder, $request);
        }

        return $formBuilder->getForm();
    }

    public function handleRequest(Request $request)
    {
        $form = $this->getForm($request);

        $this->handleForm($form, $request);
    }

    public function handleForm(FormInterface $form, Request $request)
    {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if (!empty($data)) {
                foreach ($this->overrideHandlers as $handler) {
                    $handler->overrideFromRequest($data, $request);
                }
            }
        }
    }
}
