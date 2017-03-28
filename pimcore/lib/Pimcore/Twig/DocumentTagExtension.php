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

namespace Pimcore\Twig;

use Pimcore\Model\Document\PageSnippet;
use Pimcore\Templating\Renderer\TagRenderer;

class DocumentTagExtension extends \Twig_Extension
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
            new \Twig_SimpleFunction('pimcore_*', [$this, 'renderTag'], [
                'needs_context' => true,
                'is_safe'       => ['html'],
            ])
        ];
    }

    /**
     * @see \Pimcore\View::tag
     *
     * @param array $context
     * @param string $name
     * @param string $inputName
     * @param array $options
     * @return \Pimcore\Model\Document\Tag|string
     */
    public function renderTag($context, $name, $inputName, array $options = [])
    {
        $document = $context['document'];
        if (!($document instanceof PageSnippet)) {
            return '';
        }

        return $this->tagRenderer->render($document, $name, $inputName, $options);
    }
}
