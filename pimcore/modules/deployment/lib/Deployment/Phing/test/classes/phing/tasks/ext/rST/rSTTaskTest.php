<?php

/**
 * Unit test for reStructuredText rendering task.
 *
 * PHP version 5
 *
 * @category   Tasks
 * @package    phing.tasks.ext
 * @author     Christian Weiske <cweiske@cweiske.de>
 * @license    LGPL v3 or later http://www.gnu.org/licenses/lgpl.html
 * @link       http://www.phing.info/
 * @version    SVN: $Id: 1dd3f2dc6a5d9fbe50b89683e436be109c212ad4 $
 */

require_once 'phing/BuildFileTest.php';

/**
 * Unit test for reStructuredText rendering task.
 *
 * PHP version 5
 *
 * @category   Tasks
 * @package    phing.tasks.ext
 * @author     Christian Weiske <cweiske@cweiske.de>
 * @license    LGPL v3 or later http://www.gnu.org/licenses/lgpl.html
 * @link       http://www.phing.info/
 */
class rSTTaskTest extends BuildFileTest 
{ 
    public function setUp() 
    { 
        //needed for PEAR's System class
        error_reporting(error_reporting() & ~E_STRICT);
        
        chdir(PHING_TEST_BASE . '/etc/tasks/ext/rst');
        $this->configureProject(
            PHING_TEST_BASE . '/etc/tasks/ext/rst/build.xml'
        );
        //$this->assertInLogs('Property ${version} => 1.0.1');
    }

    public function tearDown()
    {
        // remove excess file if the test failed
        @unlink(PHING_TEST_BASE . '/etc/tasks/ext/rst/files/single.html');
    }

    /**
     * Checks if a given file has been created and unlinks it afterwards.
     *
     * @param string $file relative file path
     *
     * @return void
     */
    protected function assertFileCreated($file)
    {
        $this->assertFileExists(
            PHING_TEST_BASE . '/etc/tasks/ext/rst/' . $file,
            $file . ' has not been created'
        );
        unlink(PHING_TEST_BASE . '/etc/tasks/ext/rst/' . $file);
    }



    /**
     * @expectedException BuildException
     * @expectedExceptionMessage "rst2doesnotexist" not found. Install python-docutils.
     */
    public function testGetToolPathFail()
    {
        if (version_compare(PHP_VERSION, '5.3.2') < 0) {
            $this->markTestSkipped("Need PHP 5.3.2+ for this test");
        }

        $rt = new rSTTask();
        $ref = new ReflectionClass($rt);
        $method = $ref->getMethod('getToolPath');
        $method->setAccessible(true);
        $method->invoke($rt, 'doesnotexist');
    }

    /**
     * Get the tool path previously set with setToolpath()
     */
    public function testGetToolPathCustom()
    {
        if (version_compare(PHP_VERSION, '5.3.2') < 0) {
            $this->markTestSkipped("Need PHP 5.3.2+ for this test");
        }

        $rt = new rSTTask();
        $rt->setToolpath('true');//mostly /bin/true on unix
        $ref = new ReflectionClass($rt);
        $method = $ref->getMethod('getToolPath');
        $method->setAccessible(true);
        $this->assertContains('/true', $method->invoke($rt, 'foo'));
    }



    /**
     * @expectedException BuildException
     * @expectedExceptionMessage Tool does not exist. Path:
     */
    public function testSetToolpathNotExisting()
    {
        $rt = new rSTTask();
        $rt->setToolpath('doesnotandwillneverexist');
    }

    /**
     * @expectedException BuildException
     * @expectedExceptionMessage Tool not executable. Path:
     */
    public function testSetToolpathNonExecutable()
    {
        $rt = new rSTTask();
        $rt->setToolpath(__FILE__);
    }


    public function testSingleFileParameterFile()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertFileCreated('files/single.html');
    }

    public function testSingleFileParameterFileNoExt()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertFileCreated('files/single-no-ext.html');
    }

    public function testSingleFileParameterFileFormat()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertFileCreated('files/single.3');
    }

    public function testSingleFileInvalidParameterFormat()
    {
        $this->expectBuildExceptionContaining(
            __FUNCTION__, 'Invalid parameter',
            'Invalid output format "foo", allowed are'
        );
    }

    public function testSingleFileParameterFileFormatDestination()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertFileCreated('files/single-destination.html');
    }

    public function testParameterDestinationAsDirectory()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertFileCreated('files/subdir/files/single.html');
        rmdir(PHING_TEST_BASE . '/etc/tasks/ext/rst/files/subdir/files');
        rmdir(PHING_TEST_BASE . '/etc/tasks/ext/rst/files/subdir');
    }

    public function testParameterDestinationDirectoryWithFileset()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertFileCreated('files/subdir/files/single.html');
        $this->assertFileCreated('files/subdir/files/two.html');
        rmdir(PHING_TEST_BASE . '/etc/tasks/ext/rst/files/subdir/files');
        rmdir(PHING_TEST_BASE . '/etc/tasks/ext/rst/files/subdir');
    }

    public function testParameterDestinationDirectoryWithFilesetDot()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertFileCreated('files/subdir/files/single.html');
        $this->assertFileCreated('files/subdir/files/two.html');
        rmdir(PHING_TEST_BASE . '/etc/tasks/ext/rst/files/subdir/files');
        rmdir(PHING_TEST_BASE . '/etc/tasks/ext/rst/files/subdir');
    }

    public function testParameterUptodate()
    {
        $this->executeTarget(__FUNCTION__);
        $file = PHING_TEST_BASE . '/etc/tasks/ext/rst/files/single.html';
        $this->assertFileExists($file);
        $this->assertEquals(
            0, filesize($file),
            'File size is not 0, which it should have been when'
            . ' rendering was skipped'
        );
        unlink($file);
    }

    public function testDirectoryCreation()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertFileCreated('files/a/b/c/single.html');
        rmdir(PHING_TEST_BASE . '/etc/tasks/ext/rst/files/a/b/c');
        rmdir(PHING_TEST_BASE . '/etc/tasks/ext/rst/files/a/b');
        rmdir(PHING_TEST_BASE . '/etc/tasks/ext/rst/files/a');
    }

    public function testBrokenFile()
    {
        $this->expectBuildExceptionContaining(
            __FUNCTION__, 'Broken file',
            'Rendering rST failed'
        );
        $this->assertInLogs(
            'broken.rst:2: (WARNING/2)'
            . ' Bullet list ends without a blank line; unexpected unindent.'
        );
        $this->assertFileCreated('files/broken.html');
    }

    public function testMissingFiles()
    {
        $this->expectBuildExceptionContaining(
            __FUNCTION__, 'Missing attributes/tags',
            '"file" attribute or "fileset" subtag required'
        );
    }

    public function testMultiple()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertFileCreated('files/single.html');
        $this->assertFileCreated('files/two.html');
    }

    public function testMultipleDir()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertFileCreated('files/single.html');
        $this->assertFileCreated('files/two.html');
    }

    public function testMultipleDirWildcard()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertFileCreated('files/single.html');
    }


    public function testMultipleMapper()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertFileCreated('files/single.my.html');
        $this->assertFileCreated('files/two.my.html');
    }

    /**
     * @expectedException BuildException
     * @expectedExceptionMessage No filename mapper found for "./files/single.rst"
     */
    public function testNotMatchingMapper()
    {
        $this->executeTarget(__FUNCTION__);
    }


    public function testFilterChain()
    {
        $this->executeTarget(__FUNCTION__);
        $file = PHING_TEST_BASE . '/etc/tasks/ext/rst/files/filterchain.html';
        $this->assertFileExists($file);
        $cont = file_get_contents($file);
        $this->assertContains('This is a bar.', $cont);
        unlink($file);
    }



    public function testCustomParameter()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertFileExists('files/single.html');
        $file = PHING_TEST_BASE . '/etc/tasks/ext/rst/files/single.html';
        $cont = file_get_contents($file);
        $this->assertContains('this is a custom css file', $cont);
        $this->assertContains('#FF8000', $cont);
        unlink($file);
    }
}

?>
