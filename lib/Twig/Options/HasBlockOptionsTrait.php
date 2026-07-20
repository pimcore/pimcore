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
        $options = new BlockOptions();

        while ($stream->test(Token::NAME_TYPE)) {
            $name = $stream->getCurrent()->getValue();
            $stream->next();

            $argsNode = $valueNode = $parser->parseExpression();

            if ($argsNode->hasAttribute('arguments')) {
                $valueNode = $argsNode->getNode('0');
            }

            $value = $valueNode->getAttribute('value');

            switch ($name) {
                case 'limit':
                    $options->setLimit((int) $value);

                    break;
                case 'reload':
                    $options->setReload((bool) $value);

                    break;
                case 'default':
                    $options->setDefault((int) $value);

                    break;
                case 'class':
                    $options->setClass($value);

                    break;
                default:
                    break;
            }
        }

        return $options;
    }
}
