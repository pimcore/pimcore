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
 * @category   QualityAssurance
 * @package    PHP_Depend
 * @subpackage Tokenizer
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    SVN: $Id$
 * @link       http://pdepend.org/
 */

/**
 * This tokenizer uses the internal {@link token_get_all()} function as token stream
 * generator.
 *
 * @category   QualityAssurance
 * @package    PHP_Depend
 * @subpackage Tokenizer
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 1.1.0
 * @link       http://pdepend.org/
 *
 */
class PHP_Depend_Tokenizer_Internal implements PHP_Depend_TokenizerI
{
    /**
     * Mapping between php internal tokens and php depend tokens.
     *
     * @var array(integer=>integer) $tokenMap
     */
    protected static $tokenMap = array(
        T_AS                        =>  self::T_AS,
        T_DO                        =>  self::T_DO,
        T_IF                        =>  self::T_IF,
        T_SL                        =>  self::T_SL,
        T_SR                        =>  self::T_SR,
        T_DEC                       =>  self::T_DEC,
        T_FOR                       =>  self::T_FOR,
        T_INC                       =>  self::T_INC,
        T_NEW                       =>  self::T_NEW,
        T_TRY                       =>  self::T_TRY,
        T_USE                       =>  self::T_USE,
        T_VAR                       =>  self::T_VAR,
        T_CASE                      =>  self::T_CASE,
        T_ECHO                      =>  self::T_ECHO,
        T_ELSE                      =>  self::T_ELSE,
        T_EVAL                      =>  self::T_EVAL,
        T_EXIT                      =>  self::T_EXIT,
        T_FILE                      =>  self::T_FILE,
        T_GOTO                      =>  self::T_GOTO,
        T_LINE                      =>  self::T_LINE,
        T_LIST                      =>  self::T_LIST,
        T_NS_C                      =>  self::T_NS_C,
        T_ARRAY                     =>  self::T_ARRAY,
        T_BREAK                     =>  self::T_BREAK,
        T_CLASS                     =>  self::T_CLASS,
        T_CATCH                     =>  self::T_CATCH,
        T_CLONE                     =>  self::T_CLONE,
        T_CONST                     =>  self::T_CONST,
        T_EMPTY                     =>  self::T_EMPTY,
        T_ENDIF                     =>  self::T_ENDIF,
        T_FINAL                     =>  self::T_FINAL,
        T_ISSET                     =>  self::T_ISSET,
        T_PRINT                     =>  self::T_PRINT,
        T_THROW                     =>  self::T_THROW,
        T_TRAIT                     =>  self::T_TRAIT,
        T_UNSET                     =>  self::T_UNSET,
        T_WHILE                     =>  self::T_WHILE,
        T_ENDFOR                    =>  self::T_ENDFOR,
        T_ELSEIF                    =>  self::T_ELSEIF,
        T_FUNC_C                    =>  self::T_FUNC_C,
        T_GLOBAL                    =>  self::T_GLOBAL,
        T_PUBLIC                    =>  self::T_PUBLIC,
        T_RETURN                    =>  self::T_RETURN,
        T_STATIC                    =>  self::T_STATIC,
        T_STRING                    =>  self::T_STRING,
        T_SWITCH                    =>  self::T_SWITCH,
        T_CLASS_C                   =>  self::T_CLASS_C,
        T_COMMENT                   =>  self::T_COMMENT,
        T_DECLARE                   =>  self::T_DECLARE,
        T_DEFAULT                   =>  self::T_DEFAULT,
        T_DNUMBER                   =>  self::T_DNUMBER,
        T_EXTENDS                   =>  self::T_EXTENDS,
        T_FOREACH                   =>  self::T_FOREACH,
        T_INCLUDE                   =>  self::T_INCLUDE,
        T_LNUMBER                   =>  self::T_LNUMBER,
        T_PRIVATE                   =>  self::T_PRIVATE,
        T_REQUIRE                   =>  self::T_REQUIRE,
        T_TRAIT_C                   =>  self::T_TRAIT_C,
        T_ABSTRACT                  =>  self::T_ABSTRACT,
        T_CALLABLE                  =>  self::T_CALLABLE,
        T_ENDWHILE                  =>  self::T_ENDWHILE,
        T_FUNCTION                  =>  self::T_FUNCTION,
        T_INT_CAST                  =>  self::T_INT_CAST,
        T_IS_EQUAL                  =>  self::T_IS_EQUAL,
        T_OR_EQUAL                  =>  self::T_OR_EQUAL,
        T_CONTINUE                  =>  self::T_CONTINUE,
        T_METHOD_C                  =>  self::T_METHOD_C,
        T_OPEN_TAG                  =>  self::T_OPEN_TAG,
        T_SL_EQUAL                  =>  self::T_SL_EQUAL,
        T_SR_EQUAL                  =>  self::T_SR_EQUAL,
        T_VARIABLE                  =>  self::T_VARIABLE,
        T_ENDSWITCH                 =>  self::T_ENDSWITCH,
        T_DIV_EQUAL                 =>  self::T_DIV_EQUAL,
        T_AND_EQUAL                 =>  self::T_AND_EQUAL,
        T_MOD_EQUAL                 =>  self::T_MOD_EQUAL,
        T_MUL_EQUAL                 =>  self::T_MUL_EQUAL,
        T_NAMESPACE                 =>  self::T_NAMESPACE,
        T_XOR_EQUAL                 =>  self::T_XOR_EQUAL,
        T_INTERFACE                 =>  self::T_INTERFACE,
        T_BOOL_CAST                 =>  self::T_BOOL_CAST,
        T_CHARACTER                 =>  self::T_CHARACTER,
        T_CLOSE_TAG                 =>  self::T_CLOSE_TAG,
        T_INSTEADOF                 =>  self::T_INSTEADOF,
        T_PROTECTED                 =>  self::T_PROTECTED,
        T_CURLY_OPEN                =>  self::T_CURLY_BRACE_OPEN,
        T_ENDFOREACH                =>  self::T_ENDFOREACH,
        T_ENDDECLARE                =>  self::T_ENDDECLARE,
        T_IMPLEMENTS                =>  self::T_IMPLEMENTS,
        T_NUM_STRING                =>  self::T_NUM_STRING,
        T_PLUS_EQUAL                =>  self::T_PLUS_EQUAL,
        T_ARRAY_CAST                =>  self::T_ARRAY_CAST,
        T_BOOLEAN_OR                =>  self::T_BOOLEAN_OR,
        T_INSTANCEOF                =>  self::T_INSTANCEOF,
        T_LOGICAL_OR                =>  self::T_LOGICAL_OR,
        T_UNSET_CAST                =>  self::T_UNSET_CAST,
        T_DOC_COMMENT               =>  self::T_DOC_COMMENT,
        T_END_HEREDOC               =>  self::T_END_HEREDOC,
        T_MINUS_EQUAL               =>  self::T_MINUS_EQUAL,
        T_BOOLEAN_AND               =>  self::T_BOOLEAN_AND,
        T_DOUBLE_CAST               =>  self::T_DOUBLE_CAST,
        T_INLINE_HTML               =>  self::T_INLINE_HTML,
        T_LOGICAL_AND               =>  self::T_LOGICAL_AND,
        T_LOGICAL_XOR               =>  self::T_LOGICAL_XOR,
        T_OBJECT_CAST               =>  self::T_OBJECT_CAST,
        T_STRING_CAST               =>  self::T_STRING_CAST,
        T_DOUBLE_ARROW              =>  self::T_DOUBLE_ARROW,
        T_INCLUDE_ONCE              =>  self::T_INCLUDE_ONCE,
        T_IS_IDENTICAL              =>  self::T_IS_IDENTICAL,
        T_DOUBLE_COLON              =>  self::T_DOUBLE_COLON,
        T_CONCAT_EQUAL              =>  self::T_CONCAT_EQUAL,
        T_IS_NOT_EQUAL              =>  self::T_IS_NOT_EQUAL,
        T_REQUIRE_ONCE              =>  self::T_REQUIRE_ONCE,
        T_BAD_CHARACTER             =>  self::T_BAD_CHARACTER,
        T_HALT_COMPILER             =>  self::T_HALT_COMPILER,
        T_START_HEREDOC             =>  self::T_START_HEREDOC,
        T_STRING_VARNAME            =>  self::T_STRING_VARNAME,
        T_OBJECT_OPERATOR           =>  self::T_OBJECT_OPERATOR,
        T_IS_NOT_IDENTICAL          =>  self::T_IS_NOT_IDENTICAL,
        T_OPEN_TAG_WITH_ECHO        =>  self::T_OPEN_TAG_WITH_ECHO,
        T_IS_GREATER_OR_EQUAL       =>  self::T_IS_GREATER_OR_EQUAL,
        T_IS_SMALLER_OR_EQUAL       =>  self::T_IS_SMALLER_OR_EQUAL,
        T_PAAMAYIM_NEKUDOTAYIM      =>  self::T_DOUBLE_COLON,
        T_ENCAPSED_AND_WHITESPACE   =>  self::T_ENCAPSED_AND_WHITESPACE,
        T_CONSTANT_ENCAPSED_STRING  =>  self::T_CONSTANT_ENCAPSED_STRING,
        //T_DOLLAR_OPEN_CURLY_BRACES  =>  self::T_CURLY_BRACE_OPEN,
    );

    /**
     * Mapping between php internal text tokens an php depend numeric tokens.
     *
     * @var array(string=>integer) $literalMap
     */
    protected static $literalMap = array(
        '@'              =>  self::T_AT,
        '/'              =>  self::T_DIV,
        '%'              =>  self::T_MOD,
        '*'              =>  self::T_MUL,
        '+'              =>  self::T_PLUS,
        ':'              =>  self::T_COLON,
        ','              =>  self::T_COMMA,
        '='              =>  self::T_EQUAL,
        '-'              =>  self::T_MINUS,
        '.'              =>  self::T_CONCAT,
        '$'              =>  self::T_DOLLAR,
        '`'              =>  self::T_BACKTICK,
        '\\'             =>  self::T_BACKSLASH,
        ';'              =>  self::T_SEMICOLON,
        '|'              =>  self::T_BITWISE_OR,
        '&'              =>  self::T_BITWISE_AND,
        '~'              =>  self::T_BITWISE_NOT,
        '^'              =>  self::T_BITWISE_XOR,
        '"'              =>  self::T_DOUBLE_QUOTE,
        '?'              =>  self::T_QUESTION_MARK,
        '!'              =>  self::T_EXCLAMATION_MARK,
        '{'              =>  self::T_CURLY_BRACE_OPEN,
        '}'              =>  self::T_CURLY_BRACE_CLOSE,
        '('              =>  self::T_PARENTHESIS_OPEN,
        ')'              =>  self::T_PARENTHESIS_CLOSE,
        '<'              =>  self::T_ANGLE_BRACKET_OPEN,
        '>'              =>  self::T_ANGLE_BRACKET_CLOSE,
        '['              =>  self::T_SQUARED_BRACKET_OPEN,
        ']'              =>  self::T_SQUARED_BRACKET_CLOSE,
        'use'            =>  self::T_USE,
        'goto'           =>  self::T_GOTO,
        'null'           =>  self::T_NULL,
        'self'           =>  self::T_SELF,
        'true'           =>  self::T_TRUE,
        'array'          =>  self::T_ARRAY,
        'false'          =>  self::T_FALSE,
        'trait'          =>  self::T_TRAIT,
        'parent'         =>  self::T_PARENT,
        'insteadof'      =>  self::T_INSTEADOF,
        'namespace'      =>  self::T_NAMESPACE,
        '__dir__'        =>  self::T_DIR,
        '__trait__'      =>  self::T_TRAIT_C,
        '__namespace__'  =>  self::T_NS_C
    );

    /**
     *
     * @var array(mixed=>array)
     */
    protected static $substituteTokens = array(
        T_DOLLAR_OPEN_CURLY_BRACES  =>  array('$', '{'),
    );

    /**
     * Context sensitive alternative mappings.
     *
     * @var array(integer=>array) $alternativeMap
     */
    protected static $alternativeMap = array(
        self::T_USE => array(
            self::T_OBJECT_OPERATOR  =>  self::T_STRING,
            self::T_DOUBLE_COLON     =>  self::T_STRING,
            self::T_CONST            =>  self::T_STRING,
            self::T_FUNCTION         =>  self::T_STRING,
        ),
        self::T_GOTO => array(
            self::T_OBJECT_OPERATOR  =>  self::T_STRING,
            self::T_DOUBLE_COLON     =>  self::T_STRING,
            self::T_CONST            =>  self::T_STRING,
            self::T_FUNCTION         =>  self::T_STRING,
        ),
        self::T_NULL => array(
            self::T_OBJECT_OPERATOR  =>  self::T_STRING,
            self::T_DOUBLE_COLON     =>  self::T_STRING,
            self::T_CONST            =>  self::T_STRING,
            self::T_FUNCTION         =>  self::T_STRING,
        ),
        self::T_SELF => array(
            self::T_OBJECT_OPERATOR  =>  self::T_STRING,
            self::T_DOUBLE_COLON     =>  self::T_STRING,
            self::T_CONST            =>  self::T_STRING,
            self::T_FUNCTION         =>  self::T_STRING,
        ),
        self::T_TRUE => array(
            self::T_OBJECT_OPERATOR  =>  self::T_STRING,
            self::T_DOUBLE_COLON     =>  self::T_STRING,
            self::T_CONST            =>  self::T_STRING,
            self::T_FUNCTION         =>  self::T_STRING,
        ),
        self::T_ARRAY => array(
            self::T_OBJECT_OPERATOR  =>  self::T_STRING,
        ),
        self::T_FALSE => array(
            self::T_OBJECT_OPERATOR  =>  self::T_STRING,
            self::T_DOUBLE_COLON     =>  self::T_STRING,
            self::T_CONST            =>  self::T_STRING,
            self::T_FUNCTION         =>  self::T_STRING,
        ),
        self::T_PARENT => array(
            self::T_OBJECT_OPERATOR  =>  self::T_STRING,
            self::T_DOUBLE_COLON     =>  self::T_STRING,
            self::T_CONST            =>  self::T_STRING,
        ),
        self::T_NAMESPACE => array(
            self::T_OBJECT_OPERATOR  =>  self::T_STRING,
            self::T_DOUBLE_COLON     =>  self::T_STRING,
            self::T_CONST            =>  self::T_STRING,
            self::T_FUNCTION         =>  self::T_STRING,
        ),
        self::T_DIR => array(
            self::T_OBJECT_OPERATOR  =>  self::T_STRING,
            self::T_DOUBLE_COLON     =>  self::T_STRING,
            self::T_CONST            =>  self::T_STRING,
            self::T_FUNCTION         =>  self::T_STRING,
        ),
        self::T_NS_C => array(
            self::T_OBJECT_OPERATOR  =>  self::T_STRING,
            self::T_DOUBLE_COLON     =>  self::T_STRING,
            self::T_CONST            =>  self::T_STRING,
            self::T_FUNCTION         =>  self::T_STRING,
        ),
        self::T_PARENT => array(
            self::T_OBJECT_OPERATOR  =>  self::T_STRING,
            self::T_DOUBLE_COLON     =>  self::T_STRING,
            self::T_CONST            =>  self::T_STRING,
            self::T_FUNCTION         =>  self::T_STRING,
        ),
        self::T_TRAIT_C=> array(
            self::T_OBJECT_OPERATOR  =>  self::T_STRING,
            self::T_DOUBLE_COLON     =>  self::T_STRING,
            self::T_CONST            =>  self::T_STRING,
            self::T_FUNCTION         =>  self::T_STRING,
        ),
        self::T_TRAIT => array(
            self::T_OBJECT_OPERATOR  =>  self::T_STRING,
            self::T_DOUBLE_COLON     =>  self::T_STRING,
            self::T_CONST            =>  self::T_STRING,
            self::T_FUNCTION         =>  self::T_STRING,
        ),
        self::T_INSTEADOF => array(
            self::T_OBJECT_OPERATOR  =>  self::T_STRING,
            self::T_DOUBLE_COLON     =>  self::T_STRING,
            self::T_CONST            =>  self::T_STRING,
            self::T_FUNCTION         =>  self::T_STRING,
        ),
    );

    /**
     * The source file instance.
     *
     * @var PHP_Depend_Code_File $sourceFile
     */
    protected $sourceFile = '';

    /**
     * Count of all tokens.
     *
     * @var integer $count
     */
    protected $count = 0;

    /**
     * Internal stream pointer index.
     *
     * @var integer $index
     */
    protected $index = 0;

    /**
     * Prepared token list.
     *
     * @var array(PHP_Depend_Token) $tokens
     */
    protected $tokens = null;

    /**
     * The next free identifier for unknown string tokens.
     *
     * @var integer $_unknownTokenID
     */
    private $unknownTokenID = 1000;

    /**
     * Returns the name of the source file.
     *
     * @return PHP_Depend_Code_File
     */
    public function getSourceFile()
    {
        return $this->sourceFile;
    }

    /**
     * Sets a new php source file.
     *
     * @param string $sourceFile A php source file.
     *
     * @return void
     */
    public function setSourceFile($sourceFile)
    {
        $this->tokens = null;
        $this->sourceFile = new PHP_Depend_Code_File($sourceFile);
    }

    /**
     * Returns the next token or {@link PHP_Depend_TokenizerI::T_EOF} if
     * there is no next token.
     *
     * @return PHP_Depend_Token|integer
     */
    public function next()
    {
        $this->tokenize();

        if ($this->index < $this->count) {
            return $this->tokens[$this->index++];
        }
        return self::T_EOF;
    }

    /**
     * Returns the next token type or {@link PHP_Depend_TokenizerI::T_EOF} if
     * there is no next token.
     *
     * @return integer
     */
    public function peek()
    {
        $this->tokenize();

        if (isset($this->tokens[$this->index])) {
            return $this->tokens[$this->index]->type;
        }
        return self::T_EOF;
    }

    /**
     * Returns the type of next token, after the current token. This method
     * ignores all comments between the current and the next token.
     *
     * @return integer
     * @since 0.9.12
     */
    public function peekNext()
    {
        $this->tokenize();
        
        $offset = 0;
        do {
            $type = $this->tokens[$this->index + ++$offset]->type;
        } while ($type == self::T_COMMENT || $type == self::T_DOC_COMMENT);
        return $type;
    }

    /**
     * Returns the previous token type or {@link PHP_Depend_TokenizerI::T_BOF}
     * if there is no previous token.
     *
     * @return integer
     */
    public function prev()
    {
        $this->tokenize();

        if ($this->index > 1) {
            return $this->tokens[$this->index - 2]->type;
        }
        return self::T_BOF;
    }

    /**
     * This method takes an array of tokens returned by <b>token_get_all()</b>
     * and substitutes some of the tokens with those required by PHP_Depend's
     * parser implementation.
     *
     * @param array(array) $tokens Unprepared array of php tokens.
     *
     * @return array(array)
     */
    private function substituteTokens(array $tokens)
    {
        $result = array();
        foreach ($tokens as $token) {
            $temp = (array) $token;
            $temp = $temp[0];
            if (isset(self::$substituteTokens[$temp])) {
                foreach (self::$substituteTokens[$temp] as $token) {
                    $result[] = $token;
                }
            } else {
                $result[] = $token;
            }
        }
        return $result;
    }

    /**
     * Tokenizes the content of the source file with {@link token_get_all()} and
     * filters this token stream.
     *
     * @return void
     */
    private function tokenize()
    {
        if ($this->tokens) {
            return;
        }

        $this->tokens = array();
        $this->index  = 0;
        $this->count  = 0;

        // Replace short open tags, short open tags will produce invalid results
        // in all environments with disabled short open tags.
        $source = $this->sourceFile->getSource();
        $source = preg_replace(
            array('(<\?=)', '(<\?(\s))'),
            array('<?php echo ', '<?php\1'),
            $source
        );

        if (version_compare(phpversion(), '5.3.0alpha3') < 0) {
            $tokens = PHP_Depend_Tokenizer_PHP52Helper::tokenize($source);
        } else {
            $tokens = token_get_all($source);
        }

        $tokens = $this->substituteTokens($tokens);

        // Is the current token between an opening and a closing php tag?
        $inTag = false;

        // The current line number
        $startLine = 1;

        $startColumn = 1;
        $endColumn   = 1;

        $literalMap = self::$literalMap;
        $tokenMap   = self::$tokenMap;

        // Previous found type
        $previousType = null;

        while ($token = current($tokens)) {
            $type  = null;
            $image = null;

            if (is_string($token)) {
                $token = array(null, $token);
            }

            if ($token[0] === T_OPEN_TAG) {
                $type  = $tokenMap[$token[0]];
                $image = $token[1];
                $inTag = true;
            } else if ($token[0] === T_CLOSE_TAG) {
                $type  = $tokenMap[$token[0]];
                $image = $token[1];
                $inTag = false;
            } else if ($inTag === false) {
                $type  = self::T_NO_PHP;
                $image = $this->consumeNonePhpTokens($tokens);
            } else if ($token[0] === T_WHITESPACE) {
                // Count newlines in token
                $lines = substr_count($token[1], "\n");
                if ($lines === 0) {
                    $startColumn += strlen($token[1]);
                } else {
                    $startColumn = strlen(
                        substr($token[1], strrpos($token[1], "\n") + 1)
                    ) + 1;
                }

                $startLine += $lines;
            } else {
                $value = strtolower($token[1]);
                if (isset($literalMap[$value])) {
                    // Fetch literal type
                    $type = $literalMap[$value];

                    // Check for a context sensitive alternative
                    if (isset(self::$alternativeMap[$type][$previousType])) {
                        $type = self::$alternativeMap[$type][$previousType];
                    }
                    $image = $token[1];
                } else if (isset($tokenMap[$token[0]])) {
                    $type = $tokenMap[$token[0]];
                    // Check for a context sensitive alternative
                    if (isset(self::$alternativeMap[$type][$previousType])) {
                        $type = self::$alternativeMap[$type][$previousType];
                    }

                    $image = $token[1];
                } else {
                    // This should never happen
                    // @codeCoverageIgnoreStart
                    list($type, $image) = $this->generateUnknownToken($token[1]);
                    // @codeCoverageIgnoreEnd
                }
            }

            if ($type) {
                $rtrim = rtrim($image);
                $lines = substr_count($rtrim, "\n");
                if ($lines === 0) {
                    $endColumn = $startColumn + strlen($rtrim) - 1;
                } else {
                    $endColumn = strlen(
                        substr($rtrim, strrpos($rtrim, "\n") + 1)
                    );
                }

                $endLine = $startLine + $lines;

                $token = new PHP_Depend_Token(
                    $type,
                    $rtrim,
                    $startLine,
                    $endLine,
                    $startColumn, 
                    $endColumn
                );

                // Store token in internal list
                $this->tokens[] = $token;

                // Count newlines in token
                $lines = substr_count($image, "\n");
                if ($lines === 0) {
                    $startColumn += strlen($image);
                } else {
                    $startColumn = strlen(
                        substr($image, strrpos($image, "\n") + 1)
                    ) + 1;
                }

                $startLine += $lines;
                
                // Store current type
                if ($type !== self::T_COMMENT && $type !== self::T_DOC_COMMENT) {
                    $previousType = $type;
                }
            }

            next($tokens);
        }

        $this->count = count($this->tokens);
    }

    /**
     * This method fetches all tokens until an opening php tag was found and it
     * returns the collected content. The returned value will be null if there
     * was no none php token.
     *
     * @param array &$tokens Reference to the current token stream.
     *
     * @return string
     */
    private function consumeNonePhpTokens(array &$tokens)
    {
        // The collected token content
        $content = null;

        // Fetch current token
        $token = (array) current($tokens);

        // Skipp all non open tags
        while ($token[0] !== T_OPEN_TAG_WITH_ECHO &&
               $token[0] !== T_OPEN_TAG &&
               $token[0] !== false) {

            $content .= (isset($token[1]) ? $token[1] : $token[0]);

            $token = (array) next($tokens);
        }

        // Set internal pointer one back when there was at least one none php token
        if ($token[0] !== false) {
            prev($tokens);
        }

        return $content;
    }

    /**
     * Generates a dummy/temp token for unknown string literals.
     *
     * @param string $token The unknown string token.
     *
     * @return array(integer => mixed)
     */
    private function generateUnknownToken($token)
    {
        return array($this->unknownTokenID++, $token);
    }
}
