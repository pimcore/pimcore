<?php
/*
 *  $Id: 11e8e04d563d560b31b09eda2aef8835ee3ce4c3 $
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
 * @version $Id: 11e8e04d563d560b31b09eda2aef8835ee3ce4c3 $
 * @package phing.tasks.ext.svn
 */
class SvnExportTaskTest extends AbstractSvnTaskTest { 
    public function setUp() {
        parent::setUp('SvnExportTest.xml', false);
    }

    public function testExportSimple()
    {
        $repository = PHING_TEST_BASE . '/tmp/svn';
        $this->executeTarget('exportSimple');
        $this->assertInLogs("Exporting SVN repository to '" . $repository . "'");
    }

    public function testNoRepositorySpecified()
    {
        $this->expectBuildExceptionContaining('noRepository', 
            'Repository is required',
            'is not a working copy');
    }
}
