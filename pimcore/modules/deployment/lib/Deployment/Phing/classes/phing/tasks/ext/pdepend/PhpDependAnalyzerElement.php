<?php
/**
 * $Id: f3a492fa25b203d3263e3463c1ab522c61bd0a9c $
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

require_once 'phing/system/io/PhingFile.php';

/**
 * Analyzer element for the PhpDependTask
 *
 * @package phing.tasks.ext.pdepend
 * @author  Benjamin Schultz <bschultz@proqrent.de>
 * @version $Id: f3a492fa25b203d3263e3463c1ab522c61bd0a9c $
 * @since   2.4.1
 */
class PhpDependAnalyzerElement
{
    /**
     * The type of the analyzer
     *
     * @var string
     */
    protected $_type = '';

    /**
     * The value(s) for the analyzer option
     *
     * @var array
     */
    protected $_value = array();

    /**
     * Sets the analyzer type
     *
     * @param string $type Type of the analyzer
     *
     * @return void
     */
    public function setType($type)
    {
        $this->_type = $type;

        switch ($this->_type) {
            case 'coderank-mode':
                break;

            default:
                throw new BuildException(
                    "Analyzer '" . $this->_type . "' not implemented"
                );
        }
    }

    /**
     * Get the analyzer type
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Sets the value for the analyzer
     *
     * @param string $value Value for the analyzer
     *
     * @return void
     */
    public function setValue($value)
    {
        $this->_value = array();

        $token  = ' ,;';
        $values = strtok($value, $token);

        while ($values !== false) {
            $this->_value[] = $values;
            $values = strtok($token);
        }
    }

    /**
     * Get the analyzer value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->_value;
    }
}