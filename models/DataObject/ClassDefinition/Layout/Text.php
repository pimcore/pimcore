<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Layout;

use Pimcore;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject\Concrete;
use Twig\Sandbox\SecurityError;

class Text extends Model\DataObject\ClassDefinition\Layout implements Model\DataObject\ClassDefinition\Data\LayoutDefinitionEnrichmentInterface
{
    /**
     * Static type of this element
     *
     * @internal
     *
     */
    public string $fieldtype = 'text';

    /**
     * @internal
     *
     */
    public string $html = '';

    /**
     * @internal
     *
     */
    public string $renderingClass = '';

    /**
     * @internal
     *
     */
    public string $renderingData;

    /**
     * @internal
     *
     */
    public bool $border = false;

    public function getHtml(): string
    {
        return $this->html;
    }

    /**
     * @return $this
     */
    public function setHtml(string $html): static
    {
        $this->html = $html;

        return $this;
    }

    public function getRenderingClass(): string
    {
        return $this->renderingClass;
    }

    public function setRenderingClass(string $renderingClass): void
    {
        $this->renderingClass = $renderingClass;
    }

    public function getRenderingData(): string
    {
        return $this->renderingData;
    }

    public function setRenderingData(string $renderingData): void
    {
        $this->renderingData = $renderingData;
    }

    public function getBorder(): bool
    {
        return $this->border;
    }

    public function setBorder(bool $border): void
    {
        $this->border = $border;
    }

    public function enrichLayoutDefinition(?Concrete $object, array $context = []): static
    {
        $renderer = null;
        $class = $this->getRenderingClass();
        if (!empty($class)) {
            $renderer = Model\DataObject\ClassDefinition\Helper\DynamicTextResolver::resolveRenderingClass(
                $class
            );
        }

        $context['fieldname'] = $this->getName();
        $context['layout'] = $this;

        if ($renderer instanceof DynamicTextLabelInterface) {
            $result = $renderer->renderLayoutText($this->renderingData, $object, $context);
            $this->html = $result;
        }

        $templatingEngine = Pimcore::getContainer()->get('pimcore.templating.engine.delegating');

        try {
            $twig = $templatingEngine->getTwigEnvironment(true);
            $template = $twig->createTemplate($this->html);
            $this->html = $template->render(array_merge($context,
                [
                    'object' => $object,
                ]
            ));
        } catch (SecurityError $e) {
            Logger::err((string) $e);

            $this->html = sprintf('<h2>Error</h2>Failed rendering the template: <b>%s</b>.
                Please check your twig sandbox security policy or contact the administrator.',
                substr($e->getMessage(), 0, strpos($e->getMessage(), ' in "__string')));
        } finally {
            $templatingEngine->disableSandboxExtensionFromTwigEnvironment();
        }

        return $this;
    }
}
