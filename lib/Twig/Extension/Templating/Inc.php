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

namespace Pimcore\Twig\Extension\Templating;

use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Templating\Renderer\IncludeRenderer;
use Pimcore\Twig\Extension\Templating\Traits\HelperCharsetTrait;
use Twig\Extension\RuntimeExtensionInterface;

class Inc implements RuntimeExtensionInterface
{
    use HelperCharsetTrait;

    protected IncludeRenderer $includeRenderer;

    protected EditmodeResolver $editmodeResolver;

    public function __construct(IncludeRenderer $includeRenderer, EditmodeResolver $editmodeResolver)
    {
        $this->includeRenderer = $includeRenderer;
        $this->editmodeResolver = $editmodeResolver;
    }

    public function __invoke(int|string|PageSnippet $include, array $params = [], bool $cacheEnabled = true, bool $editmode = null): string
    {
        if (null === $editmode) {
            $editmode = $this->editmodeResolver->isEditmode();
        }

        return $this->includeRenderer->render($include, $params, $editmode, $cacheEnabled);
    }
}
