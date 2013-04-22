<?php
/*
 *  $Id: 22e5a46fbdc7a368224b7b4807489525fee680c7 $
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
require_once 'phing/tasks/ext/svn/AbstractSvnTaskTest.php';

/**
 * @author Michiel Rook <mrook@php.net>
 * @version $Id: 22e5a46fbdc7a368224b7b4807489525fee680c7 $
 * @package phing.tasks.ext
 */
class SvnUpdateTaskTest extends AbstractSvnTaskTest { 
    public function setUp() { 
        parent::setUp('SvnUpdateTest.xml', false);
    }

    public function testUpdateSimple()
    {
        $repository = PHING_TEST_BASE . '/tmp/svn';
        $this->executeTarget('updateSimple');
        $this->assertInLogs("Checking out SVN repository to '" . $repository . "'");
        $this->assertInLogs("Updating SVN repository at '$repository'");
    }
}
