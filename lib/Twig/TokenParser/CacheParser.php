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

use Pimcore\Twig\Node\CacheNode;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TokenStream;
use function in_array;
use function is_int;
use function is_null;
use function is_string;
use function sprintf;

/**
 * @internal
 */
class CacheParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $lineno = $token->getLine();

        $stream = $this->parser->getStream();
        if ($stream->test(Token::BLOCK_END_TYPE)) {
            $this->throwSyntaxError('Please specify a cache key', $stream);
        }

        $key = $this->parser->parseExpression();

        $ttl = null;
        $tags = null;
        $force = null;
        while ($stream->test(Token::NAME_TYPE)) {
            $k = $stream->getCurrent()->getValue();
            $stream->next();

            $this->validateModifier($k, $stream);
            $node = $this->parseModifierArgument($k, $stream);

            switch ($k) {
                case 'ttl':
                    if ($node instanceof ConstantExpression
                        && !is_int($node->getAttribute('value'))
                        && !is_null($node->getAttribute('value'))
                    ) {
                        $this->throwSyntaxError(
                            'The "ttl" modifier requires an integer or null.',
                            $stream
                        );
                    }
                    $ttl = $node;

                    break;

                case 'tags':
                    if ($node instanceof ConstantExpression && !is_string($node->getAttribute('value'))) {
                        $this->throwSyntaxError(
                            'The "tags" modifier requires a string or an array of strings.',
                            $stream
                        );
                    }
                    $tags = $node;

                    break;

                case 'force':
                    $force = $node;

                    break;
            }
        }

        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideCacheEnd'], true);
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new CacheNode($key, $ttl, $tags, $force, $body, $lineno, $this->getTag());
    }

    public function decideCacheEnd(Token $token): bool
    {
        return $token->test('endpimcorecache');
    }

    public function getTag(): string
    {
        return 'pimcorecache';
    }

    /**
     * @throws SyntaxError
     */
    private function validateModifier(string $modifierName, TokenStream $stream): void
    {
        if (!in_array($modifierName, ['ttl', 'tags', 'force'], true)) {
            $this->throwSyntaxError(sprintf('Unknown "%s" configuration.', $modifierName), $stream);
        }
    }

    /**
     * Parses `(expression)`, the single argument of a modifier.
     *
     * @throws SyntaxError
     */
    private function parseModifierArgument(string $modifierName, TokenStream $stream): AbstractExpression
    {
        $stream->expect(Token::OPERATOR_TYPE, '(', 'A list of arguments must begin with an opening parenthesis');
        $argument = $this->parser->parseExpression();

        if (!$stream->test(Token::PUNCTUATION_TYPE, ')')) {
            $this->throwSyntaxError(
                sprintf('The "%s" modifier takes exactly one argument.', $modifierName),
                $stream
            );
        }
        $stream->next();

        return $argument;
    }

    /**
     * @throws SyntaxError
     */
    private function throwSyntaxError(string $message, TokenStream $stream): void
    {
        throw new SyntaxError($message, $stream->getCurrent()->getLine(), $stream->getSourceContext());
    }
}
