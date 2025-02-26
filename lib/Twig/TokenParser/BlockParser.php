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

use Pimcore\Twig\Node\BlockNode;
use Pimcore\Twig\Options\HasBlockOptionsTrait;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * @internal
 */
final class BlockParser extends AbstractTokenParser
{
    use HasBlockOptionsTrait;

    public function parse(Token $token): Node
    {
        $lineno = $token->getLine();

        $stream = $this->parser->getStream();
        $blockName = $stream->expect(Token::STRING_TYPE, null, 'Please specify a block name')->getValue();

        $options = $this->getBlockOptions($stream, $this->parser);
        $options->setManual(false);

        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decidePimcoreBlockEnd'], true);
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new BlockNode($blockName, $options, $body, $lineno, $this->getTag());
    }

    public function decidePimcoreBlockEnd(Token $token): bool
    {
        return $token->test('endpimcoreblock');
    }

    public function getTag()
    {
        return 'pimcoreblock';
    }
}
