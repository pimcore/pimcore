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

namespace Pimcore\Twig\Extension;

use Pimcore\Model\Document\Editable\BlockInterface;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Templating\Renderer\EditableRenderer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DocumentEditableExtension extends AbstractExtension
{
    /**
     * @var EditableRenderer
     */
    protected $editableRenderer;

    /**
     * @param EditableRenderer $editableRenderer
     */
    public function __construct(EditableRenderer $editableRenderer)
    {
        $this->editableRenderer = $editableRenderer;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('pimcore_*', [$this, 'renderEditable'], [
                'needs_context' => true,
                'is_safe' => ['html'],
            ]),
            new TwigFunction('pimcore_iterate_block', [$this, 'getBlockIterator']),
        ];

        // those are just for auto-complete, not nice, but works ;-)
        new TwigFunction('pimcore_area');
        new TwigFunction('pimcore_areablock');
        new TwigFunction('pimcore_block');
        new TwigFunction('pimcore_checkbox');
        new TwigFunction('pimcore_date');
        new TwigFunction('pimcore_embed');
        new TwigFunction('pimcore_image');
        new TwigFunction('pimcore_input');
        new TwigFunction('pimcore_link');
        new TwigFunction('pimcore_multiselect');
        new TwigFunction('pimcore_numeric');
        new TwigFunction('pimcore_pdf');
        new TwigFunction('pimcore_relation');
        new TwigFunction('pimcore_relations');
        new TwigFunction('pimcore_renderlet');
        new TwigFunction('pimcore_scheduledblock');
        new TwigFunction('pimcore_select');
        new TwigFunction('pimcore_snippet');
        new TwigFunction('pimcore_textarea');
        new TwigFunction('pimcore_video');
        new TwigFunction('pimcore_wysiwyg');
    }

    /**
     * @param array $context
     * @param string $name
     * @param string $inputName
     * @param array $options
     *
     * @return \Pimcore\Model\Document\Editable|string
     *
     * @deprecated since v6.8 and will be removed in 7. use renderEditable instead.
     */
    public function renderTag($context, $name, $inputName, array $options = [])
    {
        return $this->renderEditable($context, $name, $inputName, $options);
    }

    /**
     * @param array $context
     * @param string $name
     * @param string $inputName
     * @param array $options
     *
     * @return \Pimcore\Model\Document\Editable|string
     */
    public function renderEditable($context, $name, $inputName, array $options = [])
    {
        $document = $context['document'];
        $editmode = $context['editmode'];
        if (!($document instanceof PageSnippet)) {
            return '';
        }

        return $this->editableRenderer->render($document, $name, $inputName, $options, $editmode);
    }

    /**
     * Returns an iterator which can be used instead of while($block->loop())
     *
     * @param BlockInterface $block
     *
     * @return \Generator|int[]
     */
    public function getBlockIterator(BlockInterface $block): \Generator
    {
        while ($block->loop()) {
            yield $block->getCurrentIndex();
        }
    }
}

class_alias(DocumentEditableExtension::class, 'Pimcore\Twig\Extension\DocumentTagExtension');
