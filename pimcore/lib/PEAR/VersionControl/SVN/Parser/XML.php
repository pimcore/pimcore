<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * VersionControl_SVN_Info allows for XML formatted output. XML_Parser is used to
 * manipulate that output.
 *
 * +----------------------------------------------------------------------+
 * | This LICENSE is in the BSD license style.                            |
 * | http://www.opensource.org/licenses/bsd-license.php                   |
 * |                                                                      |
 * | Redistribution and use in source and binary forms, with or without   |
 * | modification, are permitted provided that the following conditions   |
 * | are met:                                                             |
 * |                                                                      |
 * |  * Redistributions of source code must retain the above copyright    |
 * |    notice, this list of conditions and the following disclaimer.     |
 * |                                                                      |
 * |  * Redistributions in binary form must reproduce the above           |
 * |    copyright notice, this list of conditions and the following       |
 * |    disclaimer in the documentation and/or other materials provided   |
 * |    with the distribution.                                            |
 * |                                                                      |
 * |  * Neither the name of Clay Loveless nor the names of contributors   |
 * |    may be used to endorse or promote products derived from this      |
 * |    software without specific prior written permission.               |
 * |                                                                      |
 * | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS  |
 * | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT    |
 * | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS    |
 * | FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE      |
 * | COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,  |
 * | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, |
 * | BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;     |
 * | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER     |
 * | CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT   |
 * | LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN    |
 * | ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE      |
 * | POSSIBILITY OF SUCH DAMAGE.                                          |
 * +----------------------------------------------------------------------+
 *
 * PHP version 5
 *
 * @category  VersionControl
 * @package   VersionControl_SVN
 * @author    Alexander Opitz <opitz.alexander@gmail.com>
 * @copyright 2012 Alexander Opitz
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link      http://pear.php.net/package/VersionControl_SVN
 */

require_once 'VersionControl/SVN/Parser/Exception.php';

/**
 * Class VersionControl_SVN_Parser_Info - XML Parser for Subversion Info output
 *
 * @category VersionControl
 * @package  VersionControl_SVN
 * @author   Alexander Opitz <opitz.alexander@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version  0.5.1
 * @link     http://pear.php.net/package/VersionControl_SVN
 */
class VersionControl_SVN_Parser_XML
{
    /**
     * @var array $xmlPathConfig The XML configuration (like a DTD).
     */
    protected $xmlPathConfig = array();

    /**
     * Parses given xml by xmlPathConfig of this class.
     *
     * @param string $xml The XML as string.
     *
     * @return array The processed xml as array.
     * @throws VersionControl_SVN_Parser_Exception If XML isn't parseable.
     */
    public function getParsed($xml)
    {
        $reader = new XMLReader();
        $reader->xml($xml);
        if (false === $reader) {
            throw new VersionControl_SVN_Parser_Exception(
                'Cannot instantiate XMLReader'
            );
        }
        $data = self::getParsedBody(
            $reader, $this->xmlPathConfig
        );
        $reader->close();
        return $data;
    }

    /**
     * Function to read out the xml body element.
     *
     * @param XMLReader $reader        Instance of the XMLReader.
     * @param array     $xmlPathConfig Configuration for this XML file.
     *
     * @return array The data parsed from XML
     * @throws VersionControl_SVN_Parser_Exception If XML doesn't match config.
     */
    protected static function getParsedBody(
        XMLReader $reader, array $xmlPathConfig
    ) {
        $xmlBodyEntry = key($xmlPathConfig);
        while ($reader->read()) {
            if (XMLReader::ELEMENT === $reader->nodeType
                && $xmlBodyEntry === $reader->name
            ) {
                return self::getParsedEntry(
                    $reader, $xmlBodyEntry, $xmlPathConfig[$xmlBodyEntry]
                );
            }
        }
        throw new VersionControl_SVN_Parser_Exception(
            'XML ends before body end tag.'
        );
    }

    /**
     * Function to read out the xml entry element.
     *
     * @param XMLReader $reader        Instance of the XMLReader.
     * @param string    $xmlEntry      Name of the entry.
     * @param array     $xmlPathConfig Configuration for this XML file.
     *
     * @return array The data parsed from XML
     * @throws VersionControl_SVN_Parser_Exception If XML doesn't match config.
     */
    protected static function getParsedEntry(
        XMLReader $reader, $xmlEntry, array $xmlPathConfig
    ) {
        // @var array $data The array of entry data
        $data = array();

        while ($reader->read()) {
            if (XMLReader::ELEMENT === $reader->nodeType
            ) {
                if (isset($xmlPathConfig['path'][$reader->name])) {
                    $config = $xmlPathConfig['path'][$reader->name];
                    $elementData = self::getParsedElement(
                        $reader,
                        $reader->name,
                        $config
                    );
                    if (isset($config['quantifier'])
                        && ($config['quantifier'] == '+'
                        || $config['quantifier'] == '*')
                    ) {
                        $data[$reader->name][] = $elementData;
                    } else {
                        $data[$reader->name] = $elementData;
                    }
                } else {
                    self::parseBlindEntry($reader, $reader->name);
                }
            }
            if (XMLReader::END_ELEMENT === $reader->nodeType
                && $xmlEntry === $reader->name
            ) {
                return $data;
            }
        }
        throw new VersionControl_SVN_Parser_Exception(
            'XML ends before entry end tag. 2'
        );
    }

    /**
     * Function to read out the xml element.
     *
     * @param XMLReader $reader        Instance of the XMLReader.
     * @param string    $xmlEntry      Name of the entry.
     * @param array     $xmlPathConfig Configuration for this XML file.
     *
     * @return array The data parsed from XML
     * @throws VersionControl_SVN_Parser_Exception If XML doesn't match config.
     */
    protected static function getParsedElement(
        XMLReader $reader, $xmlEntry, array $xmlPathConfig
    ) {
        // @var array $data The array of element data
        $data = array();

        if (isset($xmlPathConfig['attribute'])) {
            foreach($xmlPathConfig['attribute'] as $attribute) {
                $data[$attribute] = $reader->getAttribute($attribute);
            }
        }
        if ($reader->isEmptyElement) {
            return $data;
        }
        if (isset($xmlPathConfig['config'])
            && 'string' === $xmlPathConfig['config']
        ) {
            $data = self::getParsedString($reader, $xmlEntry);
        } else {
            $data = array_merge(
                self::getParsedEntry($reader, $xmlEntry, $xmlPathConfig),
                $data
            );
        }
        return $data;
    }

    /**
     * Function to read out a string from a XML entry.
     *
     * @param XMLReader $reader   Instance of the XMLReader.
     * @param string    $xmlEntry Name of the entry.
     *
     * @return string The string which should be read.
     * @throws VersionControl_SVN_Parser_Exception If XML doesn't match config.
     */
    protected static function getParsedString(
        XMLReader $reader, $xmlEntry
    ) {
        // @var string|null $data The text from an entry.
        $data = null;

        if ($reader->isEmptyElement) {
            return $data;
        }
        while ($reader->read()) {
            if (XMLReader::ELEMENT === $reader->nodeType
            ) {
                self::parseBlindEntry($reader, $reader->name);
            }
            if (XMLReader::TEXT === $reader->nodeType
            ) {
                $data = $reader->value;
            }
            if (XMLReader::END_ELEMENT === $reader->nodeType
                && $xmlEntry === $reader->name
            ) {
                return $data;
            }
        }
        throw new VersionControl_SVN_Parser_Exception(
            'XML ends before entry end tag. 3'
        );
    }

    /**
     * Function to read out all XML entries, which aren't configured.
     *
     * @param XMLReader $reader   Instance of the XMLReader.
     * @param string    $xmlEntry Name of the entry.
     *
     * @return void
     * @throws VersionControl_SVN_Parser_Exception If XML doesn't match config.
     */
    protected static function parseBlindEntry(
        XMLReader $reader, $xmlEntry
    ) {
        if ($reader->isEmptyElement) {
            return;
        }
        while ($reader->read()) {
            if (XMLReader::ELEMENT === $reader->nodeType
            ) {
                self::parseBlindEntry($reader, $reader->name);
            }
            if (XMLReader::END_ELEMENT === $reader->nodeType
                && $xmlEntry === $reader->name
            ) {
                return;
            }
        }
        throw new VersionControl_SVN_Parser_Exception(
            'XML ends before entry end tag. 4'
        );
    }
}
?>