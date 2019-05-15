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

use Pimcore\Twig\Node\GlossaryNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class GlossaryTokenParser extends AbstractTokenParser
{
    /**
     * @inheritDoc
     */
    public function parse(Token $token)
    {
        $lineno = $token->getLine();

        $stream = $this->parser->getStream();
        $stream->expect(Token::BLOCK_END_TYPE);

        // create body subtree
        $body = $this->parser->subparse(function (Token $token) {
            return $token->test(['endpimcoreglossary']);
        }, true);

        // end tag block end
        $stream->expect(Token::BLOCK_END_TYPE);

        return new GlossaryNode(['body' => $body], [], $lineno, $this->getTag());
    }

    /**
     * @inheritDoc
     */
    public function getTag(): string
    {
        return 'pimcoreglossary';
    }
}
