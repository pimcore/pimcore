<?php

/*
 *  $Id: 0048919b708179e9ef712896b20881bf83618845 $
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


require_once 'PHPUnit/Framework/TestCase.php';
include_once 'phing/system/io/UnixFileSystem.php';

/**
 * Unit test for UnixFileSystem
 *
 * @author Michiel Rook <mrook@php.net>
 * @package phing.system.io
 */
class UnixFileSystemTest extends PHPUnit_Framework_TestCase {

    /**
     * @var FileSystem
     */
    private $fs;
    
    public function setUp() {
        $this->fs = new UnixFileSystem();
    }
    
    public function tearDown() {
    }
    
    public function testCompare() {
        $f1 = new PhingFile(__FILE__);
        $f2 = new PhingFile(__FILE__);
        
        $this->assertEquals($this->fs->compare($f1, $f2), 0);
    }
    
    public function testHomeDirectory1() {
        $this->assertEquals($this->fs->normalize('~/test'), '~/test');
    }

    public function testHomeDirectory2() {
        $this->assertEquals($this->fs->normalize('/var/~test'), '/var/~test');
    }

    public function testHomeDirectory3() {
        $this->assertEquals($this->fs->normalize('~test'), '~test');
    }
}
