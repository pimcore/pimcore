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

namespace Pimcore\Twig\TokenParser;

use Pimcore\Twig\Node\MyPlaceholderNode;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * @internal
 *
 * The spaceless tag only removes spaces between HTML elements. This removes all newlines in a block and is suited
 * for a simple minification of CSS/JS assets.
 */
class MyPlaceholderParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $lineno = $token->getLine();

        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideMyPlaceholderEnd'], true);

        return new MyPlaceholderNode($body, $lineno, $this->getTag());
    }

    public function decideMyPlaceholderEnd(Token $token): bool
    {
        return $token->test('endmyplaceholder');
    }

    public function getTag(): string
    {
        return 'myplaceholder';
    }
}
