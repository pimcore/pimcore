<?php

namespace Pimcore\Bundle\PimcoreBundle\Controller\Traits;

use Pimcore\Bundle\PimcoreBundle\Controller\TemplateControllerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Templating\TemplateGuesser;
use Symfony\Component\HttpFoundation\Request;

trait TemplateControllerTrait
{
    /**
     * @inheritDoc
     */
    public function setViewAutoRender(Request $request, $autoRender, $engine = null)
    {
        $autoRender = (bool)$autoRender;

        if ($autoRender) {
            $request->attributes->set(TemplateControllerInterface::ATTRIBUTE_AUTO_RENDER, (bool)$autoRender);

            if (null !== $engine) {
                $request->attributes->set(TemplateControllerInterface::ATTRIBUTE_AUTO_RENDER_ENGINE, $engine);
            }
        } else {
            $attributes = [
                TemplateControllerInterface::ATTRIBUTE_AUTO_RENDER,
                TemplateControllerInterface::ATTRIBUTE_AUTO_RENDER_ENGINE
            ];

            foreach ($attributes as $attribute) {
                if ($request->attributes->has($attribute)) {
                    $request->attributes->remove($attribute);
                }
            }
        }
    }
}
