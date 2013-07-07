<?php
/**
 * $Id: 69fc758899446b96312ac12f26b461969eb41b6e $
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
 * A wrapper for the implementations of PHPMDResultFormatter.
 *
 * @package phing.tasks.ext.phpmd
 * @author  Benjamin Schultz <bschultz@proqrent.de>
 * @version $Id: 69fc758899446b96312ac12f26b461969eb41b6e $
 * @since   2.4.1
 */
class PHPMDFormatterElement
{
    /**
     * @var PHPMDResultFormatter
     */
    protected $formatter = null;

    /**
     * The type of the formatter.
     *
     * @var string
     */
    protected $type = "";

    /**
     * Whether to use file (or write output to phing log).
     *
     * @var boolean
     */
    protected $useFile = true;

    /**
     * Output file for formatter.
     *
     * @var PhingFile
     */
    protected $outfile = null;

    /**
     * Sets the formatter type.
     *
     * @param string $type Type of the formatter
     *
     * @return void
     */
    public function setType($type)
    {
        $this->type = $type;

        switch ($this->type) {
        case 'xml':
            include_once 'PHP/PMD/Renderer/XMLRenderer.php';
            break;

        case 'html':
            include_once 'PHP/PMD/Renderer/HTMLRenderer.php';
            break;

        case 'text':
            include_once 'PHP/PMD/Renderer/TextRenderer.php';
            break;

        default:
            throw new BuildException("Formatter '" . $this->type . "' not implemented");
        }
    }

    /**
     * Get the formatter type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set whether to write formatter results to file or not.
     *
     * @param boolean $useFile True or false.
     *
     * @return void
     */
    public function setUseFile($useFile)
    {
        $this->useFile = (boolean) $useFile;
    }

    /**
     * Return whether to write formatter results to file or not.
     *
     * @return boolean
     */
    public function getUseFile()
    {
        return $this->useFile;
    }

    /**
     * Sets the output file for the formatter results.
     *
     * @param PhingFile $outfile The output file
     *
     * @return void
     */
    public function setOutfile(PhingFile $outfile)
    {
        $this->outfile = $outfile;
    }

    /**
     * Get the output file.
     *
     * @return PhingFile
     */
    public function getOutfile()
    {
        return $this->outfile;
    }

    /**
     * Creates a report renderer instance based on the formatter type.
     *
     * @return PHP_PMD_AbstractRenderer
     * @throws BuildException When the specified renderer does not exist.
     */
    public function getRenderer()
    {
        switch ($this->type) {
        case 'xml':
            $renderer = new PHP_PMD_Renderer_XMLRenderer();
            break;

        case 'html':
            $renderer = new PHP_PMD_Renderer_HTMLRenderer();
            break;

        case 'text':
            $renderer = new PHP_PMD_Renderer_TextRenderer();
            break;

        default:
            throw new BuildException("PHP_MD renderer '" . $this->type . "' not implemented");
        }

        // Create a report stream
        if ($this->getUseFile() === false || $this->getOutfile() === null) {
            $stream = STDOUT;
        } else {
            $stream = fopen($this->getOutfile()->getAbsoluteFile(), 'wb');
        }

        require_once 'PHP/PMD/Writer/Stream.php';
        
        $renderer->setWriter(new PHP_PMD_Writer_Stream($stream));

        return $renderer;
    }
}