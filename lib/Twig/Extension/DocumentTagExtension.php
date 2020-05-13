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

use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Document\Tag\BlockInterface;
use Pimcore\Templating\Renderer\TagRenderer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DocumentTagExtension extends AbstractExtension
{
    /**
     * @var TagRenderer
     */
    protected $tagRenderer;

    /**
     * @param TagRenderer $tagRenderer
     */
    public function __construct(TagRenderer $tagRenderer)
    {
        $this->tagRenderer = $tagRenderer;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('pimcore_*', [$this, 'renderTag'], [
                'needs_context' => true,
                'is_safe' => ['html'],
            ]),
            new TwigFunction('pimcore_iterate_block', [$this, 'getBlockIterator'])
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
     * @see \Pimcore\View::tag
     *
     * @param array $context
     * @param string $name
     * @param string $inputName
     * @param array $options
     *
     * @return \Pimcore\Model\Document\Tag|string
     */
    public function renderTag($context, $name, $inputName, array $options = [])
    {
        $document = $context['document'];
        $editmode = $context['editmode'];
        if (!($document instanceof PageSnippet)) {
            return '';
        }

        return $this->tagRenderer->render($document, $name, $inputName, $options, $editmode);
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
