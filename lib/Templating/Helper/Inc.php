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

use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Templating\Renderer\IncludeRenderer;
use Symfony\Component\Templating\Helper\Helper;

class Inc extends Helper
{
    /**
     * @var IncludeRenderer
     */
    protected $includeRenderer;

    /**
     * @var EditmodeResolver
     */
    protected $editmodeResolver;

    /**
     * @param IncludeRenderer $includeRenderer
     * @param EditmodeResolver $editmodeResolver
     */
    public function __construct(IncludeRenderer $includeRenderer, EditmodeResolver $editmodeResolver)
    {
        $this->includeRenderer = $includeRenderer;
        $this->editmodeResolver = $editmodeResolver;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'inc';
    }

    /**
     * @param PageSnippet|int|string $include
     * @param array $params
     * @param bool $cacheEnabled
     * @param bool|null $editmode
     *
     * @return string
     */
    public function __invoke($include, array $params = [], $cacheEnabled = true, $editmode = null)
    {
        if (null === $editmode) {
            $editmode = $this->editmodeResolver->isEditmode();
        }

        return $this->includeRenderer->render($include, $params, $editmode, $cacheEnabled);
    }
}
