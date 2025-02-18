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

namespace Pimcore\Twig\Options;

use Twig\Error\SyntaxError;
use Twig\Parser;
use Twig\Token;
use Twig\TokenStream;

/**
 * @internal
 */
trait HasBlockOptionsTrait
{
    /**
     * @throws SyntaxError
     */
    private function getBlockOptions(TokenStream $stream, Parser $parser): BlockOptions
    {
        $expressionParser = $parser->getExpressionParser();
        $options = new BlockOptions();
        while ($stream->test(Token::NAME_TYPE)) {
            $argument = $stream->getCurrent()->getValue();
            $stream->next();

            $args = $expressionParser->parseArguments();
            $node = $args->getNode('0');

            switch ($argument) {
                case 'limit':
                    $options->setLimit((int) $node->getAttribute('value'));

                    break;
                case 'reload':
                    $options->setReload((bool) $node->getAttribute('value'));

                    break;
                case 'default':
                    $options->setDefault((int) $node->getAttribute('value'));

                    break;
                case 'class':
                    $options->setClass($node->getAttribute('value'));

                    break;
                default:
                    break;
            }
        }

        return $options;
    }
}
