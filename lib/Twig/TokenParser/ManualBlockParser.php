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

use Countable;
use Pimcore\Twig\Extension\DocumentEditableExtension;
use Pimcore\Twig\Node\ManualBlockNode;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TokenStream;
use function count;
use function in_array;
use function sprintf;

/**
 * @internal
 *
 * example:
 *  {% pimcoremanualblock "testblock" %}
    <div style="border: 1px solid green; ">
        {% blockiterate %}

            <div style="border: 1px solid red; float:left; margin-right: 20px;">
                {% do _block.blockStart(false) %}
                <div style="background-color: #fc0; margin-bottom: 10px; padding: 5px; border: 1px solid black;">
                    {% do _block.blockControls %}
                </div>
                <div style="width:200px; height:200px;border:1px solid black;">
                    {{ pimcore_input("myInput") }}
                </div>
                {% do _block.blockEnd() %}
            </div>

        {% endblockiterate %}
        <div style="clear:both;"></div>
    </div>
    {% endpimcoremanualblock %}
 *
 */
class ManualBlockParser extends AbstractTokenParser
{
    public function __construct(
        private DocumentEditableExtension $documentEditableExtension
    ) {

    }

    public function parse(Token $token): Node
    {
        $lineno = $token->getLine();

        $stream = $this->parser->getStream();
        $blockName = $stream->expect(Token::STRING_TYPE, null, 'Please specify a block name')->getValue();

        $expressionParser = $this->parser->getExpressionParser();
        $manual = false;
        while ($stream->test(Token::NAME_TYPE)) {
            $k = $stream->getCurrent()->getValue();
            $stream->next();

            $args = $expressionParser->parseArguments();
            $this->validateModifier($args, $k, $stream);
            $node = $args->getNode('0');

            switch ($k) {

                case 'manual':
                    $manual = (bool) $node->getAttribute('value');

                    break;
            }
        }
        $stream->expect(Token::BLOCK_END_TYPE);

        $start = $this->parser->subparse([$this, 'decideIterateStart'], true);
        $stream->expect(Token::BLOCK_END_TYPE);

        $bodyNode = $this->parser->subparse([$this, 'decideIterateEnd'], true);
        $stream->expect(Token::BLOCK_END_TYPE);

        $endNode = $this->parser->subparse([$this, 'decideIfEnd'], true);

        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new ManualBlockNode($this->documentEditableExtension, $blockName, $manual, $start, $bodyNode, $endNode, $lineno, $this->getTag());
    }

    public function decideIterateStart(Token $token): bool
    {
        return $token->test('blockiterate');
    }

    public function decideIterateEnd(Token $token): bool
    {
        return $token->test('endblockiterate');
    }

    public function decideIfEnd(Token $token): bool
    {
        return $token->test('endpimcoremanualblock');
    }

    public function getTag(): string
    {
        return 'pimcoremanualblock';
    }

    private function getArrayValue(Node $node): array
    {
        if ($node instanceof ArrayExpression) {
            $tags = $node->getKeyValuePairs();

            return array_map(static fn ($pair) => $pair['value']->getAttribute('value'), $tags);
        }

        return [$node->getAttribute('value')];
    }

    /**
     * @throws SyntaxError
     */
    private function validateModifier(Countable $args, string $modifierName, TokenStream $stream): void
    {
        if (!in_array($modifierName, ['manual'], true)) {
            $this->throwSyntaxError(sprintf('Unknown "%s" configuration.', $modifierName), $stream);
        }

        if (count($args) !== 1) {
            $this->throwSyntaxError(
                sprintf('The "%s" modifier takes exactly one argument (%d given).', $modifierName, count($args)),
                $stream
            );
        }
    }

    /**
     * @throws SyntaxError
     */
    private function throwSyntaxError(string $message, TokenStream $stream): void
    {
        throw new SyntaxError($message, $stream->getCurrent()->getLine(), $stream->getSourceContext());
    }
}
