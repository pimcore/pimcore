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
 * @subpackage Code
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    SVN: $Id$
 * @link       http://pdepend.org/
 */

/**
 * This class implements a filter that is based on the package.
 *
 * @category   QualityAssurance
 * @package    PHP_Depend
 * @subpackage Code
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2012 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 1.1.0
 * @link       http://pdepend.org/
 */
class PHP_Depend_Code_Filter_Package
    implements PHP_Depend_Code_FilterI
{
    /**
     * Regexp with ignorable package names and package name fragments.
     *
     * @var string $_pattern
     */
    private $pattern = '';

    /**
     * Constructs a new package filter for the given list of package names.
     *
     * @param array(string) $packages Package names.
     */
    public function __construct(array $packages)
    {
        $patterns = array();
        foreach ($packages as $package) {
            $patterns[] = str_replace('\*', '\S*', preg_quote($package));
        }
        $this->pattern = '#^(' . join('|', $patterns) . ')$#D';
    }

    /**
     * Returns <b>true</b> if the given node should be part of the node iterator,
     * otherwise this method will return <b>false</b>.
     *
     * @param PHP_Depend_Code_NodeI $node The context node instance.
     *
     * @return boolean
     */
    public function accept(PHP_Depend_Code_NodeI $node)
    {
        $package = null;
        // NOTE: This looks a little bit ugly and it seems better to exclude
        //       PHP_Depend_Code_Method and PHP_Depend_Code_Property, but when
        //       PDepend supports more node types, this could produce errors.
        if ($node instanceof PHP_Depend_Code_AbstractClassOrInterface) {
            $package = $node->getPackage()->getName();
        } else if ($node instanceof PHP_Depend_Code_Function) {
            $package = $node->getPackage()->getName();
        } else if ($node instanceof PHP_Depend_Code_Package) {
            $package = $node->getName();
        }

        return (preg_match($this->pattern, $package) === 0);
    }
}
