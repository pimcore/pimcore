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
 * @subpackage Util_Configuration
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    SVN: $Id$
 * @link       http://pdepend.org/
 * @since      0.10.0
 */

/**
 * This class provides the default factory for configuration creation.
 *
 * The factory provides two different service methods for the creation of a
 * configuration instance. The first service method is <em>create()</em> that
 * takes a file name as argument. It takes the settings defined in the given
 * file and merges the with the system's default settings.
 *
 * The second serivce method method <em>createDefault()</em> creates a default
 * configuration instance. Additionally it checks for default configuration files
 * within the current working directory. These files are <em>pdepend.xml.dist</em>
 * and <em>pdepend.xml</em>. If one or both files are present the factory reads
 * the settings from these files and merges them with the default settings.
 *
 * @category   QualityAssurance
 * @package    PHP_Depend
 * @subpackage Util_Configuration
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 1.1.0
 * @link       http://pdepend.org/
 * @since      0.10.0
 */
class PHP_Depend_Util_Configuration_Factory
{
    /**
     * The used configuration parser.
     *
     * @var PHP_Depend_Util_Configuration_Parser
     */
    protected $parser = null;

    /**
     * The default configuration values.
     *
     * @var stdClass
     */
    protected $default = null;

    /**
     * Constructs a new factory instance and initializes the default configuration.
     */
    public function __construct()
    {
        $home = PHP_Depend_Util_FileUtil::getUserHomeDirOrSysTempDir();

        $this->default = new stdClass();

        $this->default->cache           = new stdClass();
        $this->default->cache->driver   = 'file';
        $this->default->cache->location = $home . '/.pdepend';

        $this->default->imageConvert             = new stdClass();
        $this->default->imageConvert->fontSize   = '11';
        $this->default->imageConvert->fontFamily = 'Arial';

        $this->default->parser          = new stdClass();
        $this->default->parser->nesting = 8192;
    }

    /**
     * Creates a configuration instance that represents the custom settings
     * declared in the given configuration file.
     *
     * @param string $file The configuration file name.
     *
     * @return PHP_Depend_Util_Configuration
     * @throws InvalidArgumentException If the given file does not point to an
     *         existing configuration file.
     */
    public function create($file)
    {
        if (false === file_exists($file)) {
            throw new InvalidArgumentException(
                sprintf('The configuration file "%s" doesn\'t exist.', $file)
            );
        }
        return new PHP_Depend_Util_Configuration($this->read($file));
    }

    /**
     * Creates a default configuration instance.
     *
     * By default this method creates a configuration instance with the default
     * configuration settings.
     *
     * Additionally this method tries to find a file named <em>pdepend.xml.dist</em>
     * in the current working directory. If such a file exists, it will merge
     * the configuration settings defined in that file with the default
     * settings. Finally this method looks for a file named <em>pdepend.xml</em>
     * and will overwrite all previous settings with those declared in this
     * file.
     *
     * @return PHP_Depend_Util_Configuration
     */
    public function createDefault()
    {
        $fileName = getcwd() . DIRECTORY_SEPARATOR . 'pdepend.xml';
        if (file_exists($fileName . '.dist')) {
            $this->read($fileName . '.dist');
        }
        if (file_exists($fileName)) {
            $this->read($fileName);
        }
        return new PHP_Depend_Util_Configuration($this->default);
    }

    /**
     * Reads the settings from the given configuration file and updates the
     * existing settings.
     *
     * @param string $file A configuration source file.
     *
     * @return stdClass
     */
    protected function read($file)
    {
        $parser = $this->createOrReturnParser();
        $parser->parse($file);

        return $this->default;
    }

    /**
     * This method will create a new configuration parser or return a previously
     * created instance.
     *
     * @return PHP_Depend_Util_Configuration_Parser
     */
    protected function createOrReturnParser()
    {
        if (null === $this->parser) {
            $this->parser = new PHP_Depend_Util_Configuration_Parser($this->default);
        }
        return $this->parser;
    }
}
