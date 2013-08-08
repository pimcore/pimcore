<?php

/*
 *  $Id: 31abe34bfab832bbfaf2e76735a48e55387f5e8e $
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

require_once 'phing/tasks/ext/ComposerTask.php';
/**
 * Test class for the ComposerTask.
 *
 * @author  Nuno Costa <nuno@francodacosta.com>
 * @version $Id: 31abe34bfab832bbfaf2e76735a48e55387f5e8e $
 * @package phing.tasks.ext
 */
class ComposerTaskTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ComposerTask
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new ComposerTask;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @covers ComposerTask::setCommand
     * @covers ComposerTask::getCommand
     */
    public function testSetGetCommand()
    {
        $o = $this->object;
        $o->setCommand('foo');
        $this->assertEquals('foo', $o->getCommand());
    }
    /**
     * @covers ComposerTask::getPhp
     * @covers ComposerTask::setPhp
     */
    public function testSetGetPhp()
    {
        $o = $this->object;
        $o->setPhp('foo');
        $this->assertEquals('foo', $o->getPhp());
    }

    /**
     * @covers ComposerTask::setComposer
     * @covers ComposerTask::getComposer
     */
    public function testSetGetComposer()
    {
        $o = $this->object;
        $o->setComposer('foo');
        $this->assertEquals('foo', $o->getComposer());
    }

    /**
     * @covers ComposerTask::createArg
     */
    public function testCreateArg()
    {
        $o = $this->object;
        $arg = $o->createArg();
        $this->assertTrue(get_class($arg) == 'CommandlineArgument');
    }


}
