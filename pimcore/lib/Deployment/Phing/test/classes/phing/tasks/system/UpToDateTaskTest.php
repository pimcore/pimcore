<?php
/*
 *  $Id: 316c2a831eaa10cee2cc79fbdf480f7bb6163554 $
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
 * @author Victor Farazdagi <simple.square@gmail.com>
 * @version $Id: 316c2a831eaa10cee2cc79fbdf480f7bb6163554 $
 * @package phing.tasks.ext
 */
class UpToDateTaskTest extends BuildFileTest 
{ 

    public function setUp() 
    { 
        $this->configureProject(PHING_TEST_BASE 
                              . "/etc/tasks/system/UpToDateTest.xml");
    }

    /**
     * @group ticket-559
     */
    public function testOverrideNoPropertySet()
    {
      $this->executeTarget("overrideNoPropertySet");
      $this->assertInLogs('Property ${prop} has not been set.');
      $this->assertInLogs('Property ${prop} => updated');
      $this->assertInLogs('echo = ${prop}');
      $this->assertInLogs('echo = updated');
    }

    /**
     * @group ticket-559
     */
    public function testOverridePropertySet()
    {
      $this->executeTarget("overridePropertySet");
      $this->assertInLogs('Setting project property: prop -> value exists');
      $this->assertInLogs('Property ${prop} => value exists');
      $this->assertInLogs('Property ${prop} => updated');
      $this->assertInLogs('echo = value exists');
      $this->assertInLogs('echo = updated');
    }

}

