<?php

/*
 *  $Id: 516756889e12b6a1a07402ed14e839ecdad742b2 $
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

require_once 'phing/system/util/Properties.php';

/**
 * Unit test for Properties class
 *
 * @author Michiel Rook <mrook@php.net>
 * @package phing.system.util
 * @version $Id$
 */
class PropertiesTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Properties
     */
    private $props = null;
    
    public function setUp()
    {
        $this->props = new Properties();
    }
    
    public function tearDown()
    {
        unset($this->props);
    }
    
    public function testComments()
    {
        $file = new PhingFile(PHING_TEST_BASE .  "/etc/system/util/comments.properties");
        $this->props->load($file);
        
        $this->assertEquals($this->props->getProperty('useragent'), 'Mozilla/5.0 (Windows NT 5.1; rv:8.0.1) Gecko/20100101 Firefox/8.0.1');
        $this->assertEquals($this->props->getProperty('testline1'), 'Testline1');
        $this->assertEquals($this->props->getProperty('testline2'), 'Testline2');
    }
}
