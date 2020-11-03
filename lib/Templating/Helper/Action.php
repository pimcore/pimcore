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
use Pimcore\Targeting\Document\DocumentTargetingConfigurator;
use Pimcore\Templating\Renderer\ActionRenderer;
use Symfony\Component\Templating\Helper\Helper;

/**
 * @deprecated
 */
class Action extends Helper
{
    /**
     * @var ActionRenderer
     */
    protected $actionRenderer;

    /**
     * @var DocumentTargetingConfigurator
     */
    private $targetingConfigurator;

    /**
     * @var array
     */
    private $routingDefaults = [];

    public function __construct(
        ActionRenderer $actionRenderer,
        DocumentTargetingConfigurator $targetingConfigurator
    ) {
        $this->actionRenderer = $actionRenderer;
        $this->targetingConfigurator = $targetingConfigurator;
    }

    public function setRoutingDefaults(array $routingDefaults)
    {
        $this->routingDefaults = $routingDefaults;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'action';
    }

    /**
     * @param string $action
     * @param string $controller
     * @param string|null $module
     * @param array $attributes
     * @param array $query
     * @param array $options
     *
     * @return string
     */
    public function __invoke($action, $controller, $module = null, array $attributes = [], array $query = [], array $options = [])
    {
        $document = isset($attributes['document']) ? $attributes['document'] : null;
        if ($document && $document instanceof PageSnippet) {
            // apply best matching target group (if any)
            $this->targetingConfigurator->configureTargetGroup($document);

            $attributes = $this->actionRenderer->addDocumentAttributes($document, $attributes);
        }

        if (!$module) {
            $module = $this->routingDefaults['bundle'];
        }

        $uri = $this->actionRenderer->createControllerReference(
            $module,
            $controller,
            $action,
            $attributes,
            $query
        );

        return $this->actionRenderer->render($uri, $options);
    }
}
