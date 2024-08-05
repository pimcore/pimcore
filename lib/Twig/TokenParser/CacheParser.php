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
use function is_int;
use function is_null;
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
        $key = $stream->expect(Token::STRING_TYPE, null, 'Please specify a cache key')->getValue();

        $expressionParser = $this->parser->getExpressionParser();

        $ttl = null;
        $tags = [];
        $force = false;
        while ($stream->test(Token::NAME_TYPE)) {
            $k = $stream->getCurrent()->getValue();
            $stream->next();
            $args = $expressionParser->parseArguments();

            switch ($k) {
                case 'ttl':
                    $this->validateModifierHasSingleArgument($args, 'ttl', $stream);

                    $ttl = $args->getNode('0')->getAttribute('value');
                    if (!is_int($ttl) && ! is_null($ttl)) {
                        $this->throwSyntaxError(
                            'The "ttl" modifier requires an integer or null.',
                            $stream
                        );
                    }

                    break;
                case 'tags':
                    $this->validateModifierHasSingleArgument($args, 'tags', $stream);

                    try {
                        $node = $args->getNode('0');
                        if ($node instanceof ArrayExpression) {
                            $tags = $node->getKeyValuePairs();
                            $tags = array_map(static fn ($pair) => $pair['value']->getAttribute('value'), $tags);
                        } else {
                            $tags = [$node->getAttribute('value')];
                        }

                        $tags = new ArrayOfStrings($tags);
                        $tags = $tags->getValue();
                    } catch (ValueError|LogicException $e) {
                        $this->throwSyntaxError(
                            'The "tags" modifier requires a string or an array of strings.',
                            $stream
                        );
                    }

                    break;
                case 'force':
                    $this->validateModifierHasSingleArgument($args, 'force', $stream);
                    $force = $args->getNode('0')->getAttribute('value');
                    $force = (bool) $force;

                    break;
                default:
                    $this->throwSyntaxError(sprintf('Unknown "%s" configuration.', $k), $stream);
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
    private function validateModifierHasSingleArgument(Countable $args, string $modifierName, TokenStream $stream): void
    {
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
