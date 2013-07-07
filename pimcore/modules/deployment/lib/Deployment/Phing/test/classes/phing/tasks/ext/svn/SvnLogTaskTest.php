<?php
/*
 *  $Id: cc290a2f523dd3f5f0984e8c1924a838b5cdc1c3 $
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
 * @version $Id: cc290a2f523dd3f5f0984e8c1924a838b5cdc1c3 $
 * @package phing.tasks.ext
 */
class SvnLogTaskTest extends AbstractSvnTaskTest { 
    public function setUp() {
        parent::setUp('SvnLogTest.xml');
    }

    public function testGetLog()
    {
        $repository = PHING_TEST_BASE . '/tmp/svn';
        $this->executeTarget('getLog');
        $this->assertPropertyEquals('svn.log', "1560 | michiel.rook  | 2012-04-06T18:33:25.000000Z  | Update changelog for maintenance release\n");
    }
}
