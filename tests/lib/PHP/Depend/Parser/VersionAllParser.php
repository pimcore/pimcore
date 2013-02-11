<?php
/**
 * This file is part of PHP_Depend.
 *
 * PHP Version 5
 *
 * Copyright (c) 2008-2012, Manuel Pichler <mapi@pdepend.org>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Manuel Pichler nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   PHP
 * @package    PHP_Depend
 * @subpackage Parser
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    SVN: $Id$
 * @link       http://www.pdepend.org/
 * @since      0.9.20
 */

/**
 * Concrete parser implementation that is very tolerant and accepts language
 * constructs and keywords that are reserved in newer php versions, but not in
 * older versions.
 *
 * @category   PHP
 * @package    PHP_Depend
 * @subpackage Parser
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 1.1.0
 * @link       http://www.pdepend.org/
 * @since      0.9.20
 */
class PHP_Depend_Parser_VersionAllParser extends PHP_Depend_Parser
{

    /**
     * Will return <b>true</b> if the given <b>$tokenType</b> is a valid class
     * name part.
     *
     * @param integer $tokenType The type of a parsed token.
     *
     * @return boolean
     * @since 0.10.6
     */
    protected function isClassName($tokenType)
    {
        switch ($tokenType) {

        case self::T_DIR:
        case self::T_USE:
        case self::T_GOTO:
        case self::T_NULL:
        case self::T_NS_C:
        case self::T_TRUE:
        case self::T_CLONE:
        case self::T_FALSE:
        case self::T_TRAIT:
        case self::T_STRING:
        case self::T_TRAIT_C:
        case self::T_INSTEADOF:
        case self::T_NAMESPACE:
            return true;
        }
        return false;
    }

    /**
     * Parses a valid class or interface name and returns the image of the parsed
     * token.
     *
     * @return string
     * @throws PHP_Depend_Parser_TokenStreamEndException When the current token
     *         stream does not contain one more token.
     * @throws PHP_Depend_Parser_UnexpectedTokenException When the next available
     *         token is not a valid class name.
     */
    protected function parseClassName()
    {
        $type = $this->tokenizer->peek();
        
        if ($this->isClassName($type)) {
            return $this->consumeToken($type)->image;
        } else if ($type === self::T_EOF) {
            throw new PHP_Depend_Parser_TokenStreamEndException($this->tokenizer);
        }
        
        throw new PHP_Depend_Parser_UnexpectedTokenException(
            $this->tokenizer->next(),
            $this->tokenizer->getSourceFile()
        );
    }

    /**
     * Parses a function name from the given tokenizer and returns the string
     * literal representing the function name. If no valid token exists in the
     * token stream, this method will throw an exception.
     *
     * @return string
     * @throws PHP_Depend_Parser_UnexpectedTokenException When the next available
     *         token does not represent a valid php function name.
     * @throws PHP_Depend_Parser_TokenStreamEndException When there is no next
     *         token available in the given token stream.
     */
    public function parseFunctionName()
    {
        $type = $this->tokenizer->peek();
        switch ($type) {

        case self::T_CLONE:
        case self::T_STRING:
        case self::T_USE:
        case self::T_GOTO:
        case self::T_NULL:
        case self::T_SELF:
        case self::T_TRUE:
        case self::T_FALSE:
        case self::T_TRAIT:
        case self::T_INSTEADOF:
        case self::T_NAMESPACE:
        case self::T_DIR:
        case self::T_NS_C:
        case self::T_PARENT:
        case self::T_TRAIT_C:
            return $this->consumeToken($type)->image;

        case self::T_EOF:
            throw new PHP_Depend_Parser_TokenStreamEndException($this->tokenizer);
        }
        throw new PHP_Depend_Parser_UnexpectedTokenException(
            $this->tokenizer->next(),
            $this->tokenizer->getSourceFile()
        );
    }

    /**
     * Tests if the given token type is a valid formal parameter in the supported
     * PHP version.
     *
     * @param integer $tokenType Numerical token identifier.
     *
     * @return boolean
     * @since 1.0.0
     */
    protected function isFormalParameterTypeHint($tokenType)
    {
        switch ($tokenType) {

        case self::T_STRING:
        case self::T_CALLABLE:
        case self::T_BACKSLASH:
        case self::T_NAMESPACE:
            return true;
        }
        return false;
    }

    /**
     * Parses a formal parameter type hint that is valid in the supported PHP
     * version.
     *
     * @return PHP_Depend_Code_ASTNode
     * @since 1.0.0
     */
    protected function parseFormalParameterTypeHint()
    {
        switch ($this->tokenizer->peek()) {

        case self::T_CALLABLE:
            $this->consumeToken(self::T_CALLABLE);
            $type = $this->builder->buildAstTypeCallable();
            break;

        case self::T_STRING:
        case self::T_BACKSLASH:
        case self::T_NAMESPACE:
            $name = $this->parseQualifiedName();

            if (0 === strcasecmp('callable', $name)) {
                $type = $this->builder->buildAstTypeCallable();
            } else {
                $type = $this->builder->buildAstClassOrInterfaceReference($name);
            }
            break;
        }
        return $type;
    }

    /**
     * Parses an integer value.
     *
     * @return PHP_Depend_Code_ASTLiteral
     * @since 1.0.0
     */
    protected function parseIntegerNumber()
    {
        $token = $this->consumeToken(self::T_LNUMBER);

        if ('0' === $token->image) {
            if (self::T_STRING === $this->tokenizer->peek()) {
                $token1 = $this->consumeToken(self::T_STRING);
                if (preg_match('(^b[01]+$)', $token1->image)) {
                    $token->image     = $token->image . $token1->image;
                    $token->endLine   = $token1->endLine;
                    $token->endColumn = $token1->endColumn;
                } else {
                    throw new PHP_Depend_Parser_UnexpectedTokenException(
                        $token1,
                        $this->tokenizer->getSourceFile()
                    );
                }
            }
        }

        $literal = $this->builder->buildAstLiteral($token->image);
        $literal->configureLinesAndColumns(
            $token->startLine,
            $token->endLine,
            $token->startColumn,
            $token->endColumn
        );

        return $literal;
    }

    /**
     * This method parses a PHP version specific identifier for method and
     * property postfix expressions.
     *
     * @return PHP_Depend_Code_ASTNode
     * @since 1.0.0
     */
    protected function parsePostfixIdentifier()
    {
        switch ($this->tokenizer->peek()) {

        case self::T_STRING:
            $node = $this->parseLiteral();
            break;

        case self::T_CURLY_BRACE_OPEN:
            $node = $this->parseCompoundExpression();
            break;

        default:
            $node = $this->parseCompoundVariableOrVariableVariableOrVariable();
            break;
        }
        return $this->parseOptionalIndexExpression($node);
    }

    /**
     * Implements some quirks and hacks to support php here- and now-doc for
     * PHP 5.2.x versions :/
     *
     * @return PHP_Depend_Code_ASTHeredoc
     * @since 1.0.0
     */
    protected function parseHeredoc()
    {
        $heredoc = parent::parseHeredoc();
        if (version_compare(phpversion(), "5.3.0alpha") >= 0) {
            return $heredoc;
        }

        // Consume dangling semicolon
        $this->tokenizer->next();

        $token = $this->tokenizer->next();
        preg_match('(/\*(\'|")\*/)', $token->image, $match);

        return $heredoc;
    }

    /**
     * Tests if the next token is a valid array start delimiter in the supported
     * PHP version.
     *
     * @return boolean
     * @since 1.0.0
     */
    protected function isArrayStartDelimiter()
    {
        switch ($this->tokenizer->peek()) {

        case self::T_ARRAY:
        case self::T_SQUARED_BRACKET_OPEN:
            return true;
        }
        return false;
    }

    /**
     * Parses a php array declaration.
     *
     * @param PHP_Depend_Code_ASTArray $array The context array node.
     *
     * @return PHP_Depend_Code_ASTArray
     * @since 1.0.0
     */
    protected function parseArray(PHP_Depend_Code_ASTArray $array)
    {
        switch ($this->tokenizer->peek()) {

        case self::T_ARRAY:
            $this->consumeToken(self::T_ARRAY);
            $this->consumeComments();
            $this->consumeToken(self::T_PARENTHESIS_OPEN);
            $this->parseArrayElements($array, self::T_PARENTHESIS_CLOSE);
            $this->consumeToken(self::T_PARENTHESIS_CLOSE);
            break;

        default:
            $this->consumeToken(self::T_SQUARED_BRACKET_OPEN);
            $this->parseArrayElements($array, self::T_SQUARED_BRACKET_CLOSE);
            $this->consumeToken(self::T_SQUARED_BRACKET_CLOSE);
            break;
        }
        return $array;
    }
}
