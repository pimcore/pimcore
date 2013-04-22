<?php

/*
 *  $Id: 573d9dabe53e59e1d117b356a150b7598a4143af $
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
 
require_once 'phing/BuildFileTest.php';

/**
 * Tests for PHPCPDTask
 * 
 * @author Michiel Rook <mrook@php.net>
 * @package phing.tasks.ext
 */
class PHPCPDTaskTest extends BuildFileTest { 
        
    public function setUp() { 
        $this->configureProject(PHING_TEST_BASE . "/etc/tasks/ext/phpcpd/build.xml");
        
        if (version_compare(PHP_VERSION, '5.3.2') < 0) {
            $this->markTestSkipped("Need PHP 5.3.2+ for this test");
        }
    }

    public function testFormatterOutfile() {
        $this->executeTarget(__FUNCTION__);
        $this->assertFileExists(
            PHING_TEST_BASE . '/etc/tasks/ext/phpcpd/tempoutput'
        );
        unlink(PHING_TEST_BASE . '/etc/tasks/ext/phpcpd/tempoutput');
    }

    public function testFormatterPMD() {
        $this->executeTarget(__FUNCTION__);
        $this->assertFileExists(
            PHING_TEST_BASE . '/etc/tasks/ext/phpcpd/temp.xml'
        );
        unlink(PHING_TEST_BASE . '/etc/tasks/ext/phpcpd/temp.xml');
    }

    public function testFormatterNoFile() { 
        ob_start();
        $this->executeTarget(__FUNCTION__);
        $output = ob_get_clean();
        $this->assertContains("0.00% duplicated lines out of 4 total lines of code.", $output);
    }
}
