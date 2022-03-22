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

use Pimcore\Twig\Node\GlossaryNode;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * @internal
 *
 * @deprecated
 */
class GlossaryTokenParser extends AbstractTokenParser
{
    /**
     * {@inheritdoc}
     */
    public function parse(Token $token): Node
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.1',
            'Usage of pimcoreglossary tag is deprecated since version 10.1 and will be removed in Pimcore 11. Use pimcore_glossary Twig filter instead.'
        );

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
     * {@inheritdoc}
     */
    public function getTag(): string
    {
        return 'pimcoreglossary';
    }
}
