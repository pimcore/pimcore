<?php

/*
 *  $Id: 60cd9fb7367a947ede5035bf522e241a1a35152a $
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
 * Tests for PhpCodeSnifferTask
 * 
 * @author Michiel Rook <mrook@php.net>
 * @package phing.tasks.ext
 */
class PhpCodeSnifferTaskTest extends BuildFileTest { 
        
    public function setUp() { 
        $this->configureProject(PHING_TEST_BASE . "/etc/tasks/ext/phpcs/build.xml");
    }

    public function testNestedFormatters() {
        ob_start();
        $this->executeTarget(__FUNCTION__);
        $output = ob_get_clean();
        $this->assertContains("PHP CODE SNIFFER REPORT SUMMARY", $output);
        $this->assertFileExists(
            PHING_TEST_BASE . '/etc/tasks/ext/phpcs/report.txt'
        );
        unlink(PHING_TEST_BASE . '/etc/tasks/ext/phpcs/report.txt');
    }
}
