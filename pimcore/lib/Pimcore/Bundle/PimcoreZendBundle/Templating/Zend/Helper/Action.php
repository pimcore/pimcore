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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\PimcoreZendBundle\Templating\Zend\Helper;

use Pimcore\Bundle\PimcoreBundle\Templating\Renderer\ActionRenderer;
use Pimcore\Model\Document\PageSnippet;
use Zend\View\Helper\AbstractHelper;

class Action extends AbstractHelper
{
    /**
     * @var ActionRenderer
     */
    protected $actionRenderer;

    /**
     * @param ActionRenderer $actionRenderer
     */
    public function __construct(ActionRenderer $actionRenderer)
    {
        $this->actionRenderer = $actionRenderer;
    }

    /**
     * @param $action
     * @param $controller
     * @param $module
     * @param array $params
     * @return mixed
     */
    public function __invoke($action, $controller, $module, array $params = [])
    {
        $document = $params['document'];
        if ($document && $document instanceof PageSnippet) {
            $params = $this->actionRenderer->addDocumentParams($document, $params);
        }

        $controller = $this->actionRenderer->createControllerReference(
            $module,
            $controller,
            $action,
            $params
        );

        return $this->actionRenderer->render($controller);
    }
}
