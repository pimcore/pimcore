<?php
/*
 *  $Id: 257bd788b6185a3561f10a8de40502473076b6dd $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://phing.info>.
*/

include_once 'phing/types/DataType.php';
include_once 'phing/Project.php';
include_once 'phing/util/regexp/Regexp.php';

/**
 * A regular expression datatype.  Keeps an instance of the
 * compiled expression for speed purposes.  This compiled
 * expression is lazily evaluated (it is compiled the first
 * time it is needed).  The syntax is the dependent on which
 * regular expression type you are using.
 *
 * @author    <a href="mailto:yl@seasonfive.com">Yannick Lecaillez</a>
 * @version   $Id$
 * @access    public
 * @see       phing.util.regex.RegexMatcher
 * @package   phing.types
*/
class RegularExpression extends DataType {

    private $regexp   = null;
    /**
     * @todo Probably both $ignoreCase and $multiline should be removed
     * from attribute list of RegularExpression class: 
     * actual values are preserved on regexp *engine* level, not expression
     * object itself.
     */
    private $ignoreCase = false;
    private $multiline = false;
    
    function __construct() {
        $this->regexp  = new Regexp();
    }

    function setPattern($pattern) {
        $this->regexp->setPattern($pattern);
    }

    function setReplace($replace) {
        $this->regexp->setReplace($replace);
    }

    function getPattern($p) {
        if ( $this->isReference() ) {
            $ref = $this->getRef($p);
            return $ref->getPattern($p);
        }
        return $this->regexp->getPattern();
    }

    function getReplace($p) {
        if ( $this->isReference() ) {
            $ref = $this->getRef($p);
            return $ref->getReplace($p);
        }

        return $this->regexp->getReplace();
    }

    function setModifiers($modifiers) {
        $this->regexp->setModifiers($modifiers);
    }

    function getModifiers() {
        return $this->regexp->getModifiers();
    }
    
    function setIgnoreCase($bit) {
        $this->regexp->setIgnoreCase($bit);
    }
    
    function getIgnoreCase() {
        return $this->regexp->getIgnoreCase();
    }

    function setMultiline($multiline) {
        $this->regexp->setMultiline($multiline);
    }

    function getMultiline() {
        return $this->regexp->getMultiline();
    }
    
    function getRegexp(Project $p) {
        if ( $this->isReference() ) {
            $ref = $this->getRef($p);
            return $ref->getRegexp($p);
        }
        return $this->regexp;
    }

    function getRef(Project $p) {
        if ( !$this->checked ) {
            $stk = array();
            array_push($stk, $this);
            $this->dieOnCircularReference($stk, $p);            
        }

        $o = $this->ref->getReferencedObject($p);
        if ( !($o instanceof RegularExpression) ) {
            throw new BuildException($this->ref->getRefId()." doesn't denote a RegularExpression");
        } else {
            return $o;
        }
    }
}


