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

namespace Pimcore\Twig\TokenParser;

use Pimcore\Twig\Node\AssetCompressNode;
use Twig_Token;

/**
 * The spaceless tag only removes spaces between HTML elements. This removes all newlines in a block and is suited
 * for a simple minification of CSS/JS assets.
 */
class AssetCompressParser extends \Twig_TokenParser
{
    public function parse(Twig_Token $token)
    {
        $lineno = $token->getLine();

        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideAssetCompressEnd'], true);
        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

        return new AssetCompressNode($body, $lineno, $this->getTag());
    }

    public function decideAssetCompressEnd(Twig_Token $token)
    {
        return $token->test('endpimcoreassetcompress');
    }

    public function getTag()
    {
        return 'pimcoreassetcompress';
    }
}
