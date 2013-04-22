<?php

/**
 *  $Id: a33d8c08ba08deb521e04dbe9f83b73d9927b723 $
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
 * 
 * @package phing.util
 */
require_once 'phing/BuildFileTest.php';
require_once 'phing/types/PearPackageFileSet.php';

/**
 * Testcases for phing.types.PearPackageFileSet
 * 
 * @author  Christian Weiske <cweiske@cweiske.de>
 * @package phing.types
 */
class PearPackageFileSetTest extends BuildFileTest 
{ 
    public function setUp() 
    {
        //needed for PEAR's Config and Registry classes
        error_reporting(error_reporting() & ~E_DEPRECATED & ~E_STRICT);
    }

    public function testGetDirectoryScannerConsoleGetopt()
    {
        $ppfs = new PearPackageFileSet();
        $ppfs->setPackage('console_getopt');
        $ppfs->setRole('php');
        $ds = $ppfs->getDirectoryScanner(new Project());

        $arFiles = $ds->getIncludedFiles();
        $this->assertInternalType(
            'array', $arFiles, 'getIncludedFiles returned no array'
        );
        $this->assertEquals(1, count($arFiles));
        $this->assertContains('Console/Getopt.php', $arFiles);

        $fullPath = $ds->getBaseDir() . reset($arFiles);
        $this->assertTrue(
            file_exists($fullPath), 'File does not exist: ' . $fullPath
        );
    }

    public function testRoleDoc()
    {
        $ppfs = new PearPackageFileSet();
        $ppfs->setPackage('pear.phpunit.de/phpunit');
        $ppfs->setRole('doc');
        $ds = $ppfs->getDirectoryScanner(new Project());

        $arFiles = $ds->getIncludedFiles();
        $this->assertContains('LICENSE', $arFiles);
        foreach ($arFiles as $file) {
            $this->assertNotContains(
                '.php', $file, 'php files should not be in there'
            );
        }
    }

    public function testGetDir()
    {
        $ppfs = new PearPackageFileSet();
        $ppfs->setPackage('console_getopt');
        $ppfs->setRole('php');
        $ppfs->getDirectoryScanner(new Project());

        $dir = $ppfs->getDir();
        $this->assertTrue(
            file_exists($dir), 'Directory does not exist: ' . $dir
        );
        $this->assertTrue(
            is_dir($dir), '$dir is not a directory: ' . $dir
        );
    }

    public function testGetDirWithoutScanner()
    {
        $ppfs = new PearPackageFileSet();
        $ppfs->setPackage('console_getopt');
        $ppfs->setRole('php');

        $dir = $ppfs->getDir();
        $this->assertTrue(
            file_exists($dir), 'Directory does not exist: ' . $dir
        );
        $this->assertTrue(
            is_dir($dir), '$dir is not a directory: ' . $dir
        );
    }

    /**
     * @expectedException BuildException
     * @expectedExceptionMessage Invalid package name
     */
    public function testSetPackageInvalid()
    {
        $ppfs = new PearPackageFileSet();
        $ppfs->setPackage('pear.php.net/console_getopt/thisiswrong');
    }
}
?>
