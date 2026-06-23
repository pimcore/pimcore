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

namespace Pimcore\Twig\TokenParser;

use Pimcore\Twig\Node\AssetCompressNode;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * @internal
 *
 * The spaceless tag only removes spaces between HTML elements. This removes all newlines in a block and is suited
 * for a simple minification of CSS/JS assets.
 */
class AssetCompressParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $lineno = $token->getLine();

        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideAssetCompressEnd'], true);
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new AssetCompressNode($body, $lineno, $this->getTag());
    }

    public function decideAssetCompressEnd(Token $token): bool
    {
        return $token->test('endpimcoreassetcompress');
    }

    public function getTag(): string
    {
        return 'pimcoreassetcompress';
    }
}
