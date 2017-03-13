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
