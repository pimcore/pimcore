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
use LogicException;
use Pimcore\Model\Document;
use Pimcore\Twig\Extension\DocumentEditableExtension;
use Pimcore\Twig\Node\BlockNode;
use Pimcore\Twig\Node\CacheNode;
use Pimcore\ValueObject\Collection\ArrayOfStrings;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TokenStream;
use ValueError;
use function count;
use function in_array;
use function is_int;
use function is_null;
use function sprintf;

/**
 * @internal
 */
class BlockParser extends AbstractTokenParser
{
    public function __construct(
        private DocumentEditableExtension $documentEditableExtension
    )
    {

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

        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideCacheEnd'], true);
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new BlockNode($this->documentEditableExtension, $blockName, $manual, $body, $lineno, $this->getTag());
    }

    public function decideCacheEnd(Token $token): bool
    {
        return $token->test('endpimcoreblockeditable');
    }

    public function getTag(): string
    {
        return 'pimcoreblockeditable';
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
