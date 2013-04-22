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
 * Tests for PHPLOCTask
 * 
 * @author Michiel Rook <mrook@php.net>
 * @package phing.tasks.ext
 */
class PHPLOCTaskTest extends BuildFileTest { 
        
    public function setUp() { 
        $this->configureProject(PHING_TEST_BASE . "/etc/tasks/ext/phploc/build.xml");
        
        if (version_compare(PHP_VERSION, '5.3.2') < 0) {
            $this->markTestSkipped("Need PHP 5.3.2+ for this test");
        }
    }

    public function testReportText() {
        $this->executeTarget(__FUNCTION__);
        $this->assertFileExists(
            PHING_TEST_BASE . '/etc/tasks/ext/phploc/phploc-report.txt'
        );
        unlink(PHING_TEST_BASE . '/etc/tasks/ext/phploc/phploc-report.txt');
    }

    public function testReportCSV() {
        $this->executeTarget(__FUNCTION__);
        $this->assertFileExists(
            PHING_TEST_BASE . '/etc/tasks/ext/phploc/phploc-report.csv'
        );
        unlink(PHING_TEST_BASE . '/etc/tasks/ext/phploc/phploc-report.csv');
    }

    public function testReportXML() {
        $this->executeTarget(__FUNCTION__);
        $this->assertFileExists(
            PHING_TEST_BASE . '/etc/tasks/ext/phploc/phploc-report.xml'
        );
        unlink(PHING_TEST_BASE . '/etc/tasks/ext/phploc/phploc-report.xml');
    }
}
