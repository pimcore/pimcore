<?php

namespace Pimcore\Bundle\PimcoreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Templating\TemplateReferenceInterface;

interface TemplateControllerInterface
{
    const ATTRIBUTE_TEMPLATE_CONTROLLER = '_template_controller';
    const ATTRIBUTE_AUTO_RENDER = '_template_controller_auto_render';
    const ATTRIBUTE_AUTO_RENDER_ENGINE = '_template_controller_auto_render_engine';

    /**
     * Enable view auto-rendering without depending on the Template annotation
     *
     * @param Request $request
     * @param bool $autoRender
     * @param string|null $engine
     * @return
     */
    public function setViewAutoRender(Request $request, $autoRender, $engine = null);

    /**
     * Get template reference for the current request
     *
     * @param Request $request
     * @param string|null $engine
     *
     * @return TemplateReferenceInterface
     */
    public function getTemplateReference(Request $request, $engine = null);
}
