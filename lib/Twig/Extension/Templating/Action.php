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

namespace Pimcore\Twig\Extension\Templating;

<<<<<<<< HEAD:lib/Twig/Extension/Templating/Action.php
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Targeting\Document\DocumentTargetingConfigurator;
use Pimcore\Twig\Extension\Templating\Traits\HelperCharsetTrait;
use Pimcore\Templating\Renderer\ActionRenderer;
use Twig\Extension\RuntimeExtensionInterface;

class Action implements RuntimeExtensionInterface
{
    use HelperCharsetTrait;

    /**
     * @var ActionRenderer
     */
    protected $actionRenderer;
========
@trigger_error(
    'Pimcore\Templating\Helper\Action is deprecated since version 6.8.0 and will be removed in 7.0.0. ' .
    ' Use ' . \Pimcore\Twig\Extension\Templating\Action::class . ' instead.',
    E_USER_DEPRECATED
);
>>>>>>>> f48440fd1b... [Templating] ease migration with template helpers (#7463):lib/Templating/Helper/Action.php

class_exists(\Pimcore\Twig\Extension\Templating\Action::class);

if (false) {
    /**
     * @deprecated since Pimcore 6.8, use Pimcore\Twig\Extension\Templating\Action
     */
<<<<<<<< HEAD:lib/Twig/Extension/Templating/Action.php
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
========
    class Action extends \Pimcore\Twig\Extension\Templating\Action {
>>>>>>>> f48440fd1b... [Templating] ease migration with template helpers (#7463):lib/Templating/Helper/Action.php

    }
}
