<?php
/**
 * This file is part of PHP_PMD.
 *
 * PHP Version 5
 *
 * Copyright (c) 2008-2012, Manuel Pichler <mapi@phpmd.org>.
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
 * @category  PHP
 * @package   PHP_PMD
 * @author    Manuel Pichler <mapi@phpmd.org>
 * @copyright 2008-2012 Manuel Pichler. All rights reserved.
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   SVN: $Id$
 * @link      http://phpmd.org
 */

require_once 'PHP/Depend/Autoload.php';

/**
 * Simple factory that is used to return a ready to use PHP_Depend instance.
 *
 * @category  PHP
 * @package   PHP_PMD
 * @author    Manuel Pichler <mapi@phpmd.org>
 * @copyright 2008-2012 Manuel Pichler. All rights reserved.
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   Release: 1.4.1
 * @link      http://phpmd.org
 */
class PHP_PMD_ParserFactory
{
    /**
     * Creates a new factory instance.
     */
    public function __construct()
    {
        $autoload = new PHP_Depend_Autoload();
        $autoload->register();
    }

    /**
     * Creates the used PHP_PMD_Parser analyzer instance.
     *
     * @param PHP_PMD $phpmd The context php mess detector instance.
     *
     * @return PHP_PMD_Parser
     */
    public function create(PHP_PMD $phpmd)
    {
        include_once 'PHP/PMD/Parser.php';

        $pdepend = $this->createInstance();
        $pdepend = $this->init($pdepend, $phpmd);

        return new PHP_PMD_Parser($pdepend);
    }

    /**
     * Creates a clean php depend instance with some base settings.
     *
     * @return PHP_Depend
     */
    private function createInstance()
    {
        $factory = new PHP_Depend_Util_Configuration_Factory();
        return new PHP_Depend($factory->createDefault());
    }

    /**
     * Configures the given PHP_Depend instance based on some user settings.
     *
     * @param PHP_Depend $pdepend The context php depend instance.
     * @param PHP_PMD    $phpmd   The calling/parent php mess detector.
     *
     * @return PHP_Depend
     */
    private function init(PHP_Depend $pdepend, PHP_PMD $phpmd)
    {
        $this->initInput($pdepend, $phpmd);
        $this->initIgnores($pdepend, $phpmd);
        $this->initExtensions($pdepend, $phpmd);

        return $pdepend;
    }

    /**
     * Configures the input source.
     *
     * @param PHP_Depend $pdepend The context php depend instance.
     * @param PHP_PMD    $phpmd   The calling/parent php mess detector.
     *
     * @return void
     */
    private function initInput(PHP_Depend $pdepend, PHP_PMD $phpmd)
    {
        foreach (explode(',', $phpmd->getInput()) as $path) {
            if (is_dir(trim($path))) {
                $pdepend->addDirectory(trim($path));
            } else {
                $pdepend->addFile(trim($path));
            }
        }
    }

    /**
     * Initializes the ignored files and path's.
     *
     * @param PHP_Depend $pdepend The context php depend instance.
     * @param PHP_PMD    $phpmd   The calling/parent php mess detector.
     *
     * @return void
     */
    private function initIgnores(PHP_Depend $pdepend, PHP_PMD $phpmd)
    {
        if (count($phpmd->getIgnorePattern()) > 0) {
            $pdepend->addFileFilter(
                new PHP_Depend_Input_ExcludePathFilter($phpmd->getIgnorePattern())
            );
        }
    }

    /**
     * Initializes the accepted php source file extensions.
     *
     * @param PHP_Depend $pdepend The context php depend instance.
     * @param PHP_PMD    $phpmd   The calling/parent php mess detector.
     *
     * @return void
     */
    private function initExtensions(PHP_Depend $pdepend, PHP_PMD $phpmd)
    {
        if (count($phpmd->getFileExtensions()) > 0) {
            $pdepend->addFileFilter(
                new PHP_Depend_Input_ExtensionFilter($phpmd->getFileExtensions())
            );
        }
    }
}
