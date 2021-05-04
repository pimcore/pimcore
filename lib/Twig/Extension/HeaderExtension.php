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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Twig\Extension;

use Pimcore\Twig\Extension\Templating\HeadLink;
use Pimcore\Twig\Extension\Templating\HeadMeta;
use Pimcore\Twig\Extension\Templating\HeadScript;
use Pimcore\Twig\Extension\Templating\HeadStyle;
use Pimcore\Twig\Extension\Templating\HeadTitle;
use Pimcore\Twig\Extension\Templating\InlineScript;
use Pimcore\Twig\Extension\Templating\Placeholder;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @internal
 */
class HeaderExtension extends AbstractExtension
{
    /**
     * @var HeadLink
     */
    private $headLink;

    /**
     * @var HeadMeta
     */
    private $headMeta;

    /**
     * @var HeadScript
     */
    private $headScript;

    /**
     * @var HeadStyle
     */
    private $headStyle;

    /**
     * @var HeadTitle
     */
    private $headTitle;

    /**
     * @var InlineScript
     */
    private $inlineScript;

    /**
     * @var Placeholder
     */
    private $placeholder;

    /**
     * @param HeadLink $headLink
     * @param HeadMeta $headMeta
     * @param HeadScript $headScript
     * @param HeadStyle $headStyle
     * @param HeadTitle $headTitle
     * @param InlineScript $inlineScript
     * @param Placeholder $placeholder
     *
     */
    public function __construct(HeadLink $headLink, HeadMeta $headMeta, HeadScript $headScript, HeadStyle $headStyle, HeadTitle $headTitle, InlineScript $inlineScript, Placeholder $placeholder)
    {
        $this->headLink = $headLink;
        $this->headMeta = $headMeta;
        $this->headScript = $headScript;
        $this->headStyle = $headStyle;
        $this->headTitle = $headTitle;
        $this->inlineScript = $inlineScript;
        $this->placeholder = $placeholder;
    }

    public function getFunctions(): array
    {
        $options = [
            'is_safe' => ['html'],
        ];

        // as runtime extension classes are invokable, we can pass them directly as callable
        return [
            new TwigFunction('pimcore_head_link', $this->headLink, $options),
            new TwigFunction('pimcore_head_meta', $this->headMeta, $options),
            new TwigFunction('pimcore_head_script', $this->headScript, $options),
            new TwigFunction('pimcore_head_style', $this->headStyle, $options),
            new TwigFunction('pimcore_head_title', $this->headTitle, $options),
            new TwigFunction('pimcore_inline_script', $this->inlineScript, $options),
            new TwigFunction('pimcore_placeholder', $this->placeholder, $options),
        ];
    }
}
