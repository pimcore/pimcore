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

namespace Pimcore\Templating\Helper;

use Pimcore\Model\Document\PageSnippet;
use Pimcore\Templating\Renderer\ActionRenderer;
use Symfony\Component\Templating\Helper\Helper;

class Action extends Helper
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
     * @inheritDoc
     */
    public function getName()
    {
        return 'action';
    }

    /**
     * @param $action
     * @param $controller
     * @param $module
     * @param array $params
     * @return mixed
     */
    public function __invoke($action, $controller, $module = null, array $params = [])
    {
        $document = isset($params['document']) ? $params['document'] : null;
        if ($document && $document instanceof PageSnippet) {
            $params = $this->actionRenderer->addDocumentParams($document, $params);
        }

        if (!$module) {
            $module = "AppBundle";
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
