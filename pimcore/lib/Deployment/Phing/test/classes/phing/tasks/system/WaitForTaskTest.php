<?php
/*
 *  $Id: 211b56411feb1669bde6b9a74896bd2ad93a6552 $
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
 * Tests the WaitFor Task
 *
 * @author  Michiel Rook <mrook@php.net>
 * @version $Id: 211b56411feb1669bde6b9a74896bd2ad93a6552 $
 * @package phing.tasks.system
 */
class WaitForTaskTest extends BuildFileTest
{

    public function setUp()
    {
        $this->configureProject(
            PHING_TEST_BASE . '/etc/tasks/system/WaitForTest.xml'
        );
    }

    public function testConditionMet()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertInLogs('waitfor: condition was met');
    }

    public function testTimeout()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertInLogs('waitfor: timeout');
        $this->assertEquals('true', $this->project->getProperty('waitfor.timeout'));
    }
}

?>