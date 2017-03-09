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
     */
    public function setViewAutoRender(Request $request, $autoRender, $engine = null);

}
