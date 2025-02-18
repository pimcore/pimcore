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

use Pimcore\Twig\Node\ManualBlockNode;
use Pimcore\Twig\Options\HasBlockOptionsTrait;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * @internal
 */
final class ManualBlockParser extends AbstractTokenParser
{
    use HasBlockOptionsTrait;

    public function parse(Token $token): Node
    {
        $lineno = $token->getLine();

        $stream = $this->parser->getStream();
        $blockName = $stream->expect(Token::STRING_TYPE, null, 'Please specify a block name')->getValue();

        $options = $this->getBlockOptions($stream, $this->parser);
        $options->setManual(true);

        $stream->expect(Token::BLOCK_END_TYPE);

        $startNode = $this->parser->subparse([$this, 'decideIterateStart'], true);
        $stream->expect(Token::BLOCK_END_TYPE);

        $bodyNode = $this->parser->subparse([$this, 'decideIterateEnd'], true);
        $stream->expect(Token::BLOCK_END_TYPE);

        $endNode = $this->parser->subparse([$this, 'decidePimcoreManualBlockEnd'], true);

        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new ManualBlockNode(
            $blockName,
            $options,
            $startNode,
            $bodyNode,
            $endNode,
            $lineno,
            $this->getTag()
        );
    }

    public function decideIterateStart(Token $token): bool
    {
        return $token->test('blockiterate');
    }

    public function decideIterateEnd(Token $token): bool
    {
        return $token->test('endblockiterate');
    }

    public function decidePimcoreManualBlockEnd(Token $token): bool
    {
        return $token->test('endpimcoremanualblock');
    }

    public function getTag()
    {
        return 'pimcoremanualblock';
    }
}
