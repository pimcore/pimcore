<?php
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

namespace Pimcore\Http\Request\Resolver;

use Pimcore\Templating\Model\ViewModel;
use Pimcore\Templating\Model\ViewModelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ViewModelResolver extends AbstractRequestResolver
{
    const ATTRIBUTE_VIEW_MODEL = '_view_model';

    /**
     * @var TemplateVarsResolver
     */
    protected $templateVarsResolver;

    /**
     * @param RequestStack $requestStack
     * @param TemplateVarsResolver $templateVarsResolver
     */
    public function __construct(RequestStack $requestStack, TemplateVarsResolver $templateVarsResolver)
    {
        parent::__construct($requestStack);

        $this->templateVarsResolver = $templateVarsResolver;
    }

    /**
     * Get or create view model
     *
     * @param Request|null $request
     * @param bool $create
     *
     * @return ViewModel|ViewModelInterface|null
     */
    public function getViewModel(Request $request = null, $create = true)
    {
        if (null === $request) {
            $request = $this->getCurrentRequest();
        }

        if ($request->attributes->has(static::ATTRIBUTE_VIEW_MODEL)) {
            return $request->attributes->get(static::ATTRIBUTE_VIEW_MODEL);
        }

        if (!$create) {
            return null;
        }

        $viewModel = $this->createViewModel($request);
        $this->setViewModel($request, $viewModel);

        return $viewModel;
    }

    /**
     * Create a view model
     *
     * @param Request|null $request
     *
     * @return ViewModel
     */
    public function createViewModel(Request $request = null)
    {
        if (null === $request) {
            $request = $this->getCurrentRequest();
        }

        $vars      = $this->templateVarsResolver->getTemplateVars($request);
        $viewModel = new ViewModel($vars);

        return $viewModel;
    }

    /**
     * Set view model on request
     *
     * @param Request $request
     * @param ViewModelInterface $viewModel
     */
    public function setViewModel(Request $request, ViewModelInterface $viewModel)
    {
        $request->attributes->set(static::ATTRIBUTE_VIEW_MODEL, $viewModel);
    }
}
