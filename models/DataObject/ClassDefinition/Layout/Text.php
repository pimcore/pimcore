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

use Pimcore\Model;
use Pimcore\Model\DataObject\Concrete;

class Text extends Model\DataObject\ClassDefinition\Layout implements Model\DataObject\ClassDefinition\Data\LayoutDefinitionEnrichmentInterface
{
    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public string $fieldtype = 'text';

    /**
     * @internal
     *
     * @var string
     */
    public string $html = '';

    /**
     * @internal
     *
     * @var string
     */
    public string $renderingClass;

    /**
     * @internal
     *
     * @var string
     */
    public string $renderingData;

    /**
     * @internal
     *
     * @var bool
     */
    public bool $border = false;

    /**
     * @return string
     */
    public function getHtml(): string
    {
        return $this->html;
    }

    /**
     * @param string $html
     *
     * @return $this
     */
    public function setHtml(string $html): static
    {
        $this->html = $html;

        return $this;
    }

    /**
     * @return string
     */
    public function getRenderingClass(): string
    {
        return $this->renderingClass;
    }

    public function setRenderingClass(string $renderingClass)
    {
        $this->renderingClass = $renderingClass;
    }

    /**
     * @return string
     */
    public function getRenderingData(): string
    {
        return $this->renderingData;
    }

    public function setRenderingData(string $renderingData)
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

    /**
     * {@inheritdoc}
     */
    public function enrichLayoutDefinition(/* ?Concrete */ ?Concrete $object, /* array */ array $context = []): Text|Model\DataObject\ClassDefinition\Data\LayoutDefinitionEnrichmentInterface|static // : static
    {
        $renderer = Model\DataObject\ClassDefinition\Helper\DynamicTextResolver::resolveRenderingClass(
            $this->getRenderingClass()
        );

        $context['fieldname'] = $this->getName();
        $context['layout'] = $this;

        if ($renderer instanceof DynamicTextLabelInterface) {
            $result = $renderer->renderLayoutText($this->renderingData, $object, $context);
            $this->html = $result;
        }

        $templatingEngine = \Pimcore::getContainer()->get('pimcore.templating.engine.delegating');
        $twig = $templatingEngine->getTwigEnvironment();
        $template = $twig->createTemplate($this->html);
        $this->html = $template->render(array_merge($context,
            [
                'object' => $object,
            ]
        ));

        return $this;
    }
}
