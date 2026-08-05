<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Twig\Extension;

use Exception;
use Generator;
use Pimcore\Model\Document\Editable\BlockInterface;
use Pimcore\Model\Document\Editable\EditableInterface;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Templating\Renderer\EditableRenderer;
use Pimcore\Twig\TokenParser\BlockParser;
use Pimcore\Twig\TokenParser\ManualBlockParser;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @internal
 */
class DocumentEditableExtension extends AbstractExtension
{
    protected EditableRenderer $editableRenderer;

    public function __construct(EditableRenderer $editableRenderer)
    {
        $this->editableRenderer = $editableRenderer;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('pimcore_*', [$this, 'renderEditable'], [
                'needs_context' => true,
                'is_safe' => ['html'],
            ]),
            new TwigFunction('pimcore_iterate_block', [$this, 'getBlockIterator']),
            new TwigFunction(
                'pimcore_count_block',
                [$this, 'getBlockCount'],
                ['needs_context' => true]
            ),
        ];

        // @phpstan-ignore-next-line those are just for auto-complete, not nice, but works ;-)
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
        new TwigFunction('pimcore_table');
        new TwigFunction('pimcore_textarea');
        new TwigFunction('pimcore_video');
        new TwigFunction('pimcore_wysiwyg');
    }

    /**
     * @internal
     *
     * @throws Exception
     */
    public function renderEditable(
        array $context,
        string $type,
        string $name,
        array $options = []
    ): string|EditableInterface {
        $document = $context['document'] ?? null;
        if (!($document instanceof PageSnippet)) {
            return '';
        }
        $editmode = $context['editmode'] ?? false;

        return $this->editableRenderer->render($document, $type, $name, $options, $editmode);
    }

    /**
     * Returns an iterator which can be used instead of while($block->loop())
     *
     * @internal
     *
     *
     */
    public function getBlockIterator(BlockInterface $block): Generator
    {
        return $block->getIterator();
    }

    /**
     * @internal
     *
     */
    public function getBlockCount(array $context, string $name): int
    {
        $block = $this->renderEditable($context, 'block', $name);
        if ($block instanceof BlockInterface) {
            return $block->getCount();
        } else {
            return 0;
        }
    }

    public function getTokenParsers(): array
    {
        return [
            new BlockParser(),
            new ManualBlockParser(),
        ];
    }
}
