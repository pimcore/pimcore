<?php

/*
 *  $Id: 27436e9681c8821466a0ae66c589c7e6359d0d0d $
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
 * @author Bryan Davis <bpd@keynetics.com>
 * @package phing.tasks.system
 */
class ImportTaskTest extends BuildFileTest { 
        
    public function setUp() { 
        $this->configureProject(PHING_TEST_BASE . "/etc/tasks/importing.xml");
    }

    public function testOverloadedTarget () {
      $f1 = new PhingFile(PHING_TEST_BASE . "/etc/tasks/importing.xml");
      
      $this->executeTarget("main");
      $this->assertInLogs("This is " . $f1->getAbsolutePath() . " main target.");
    }
        
    public function testImportedTarget () {
      $f1 = new PhingFile(PHING_TEST_BASE . "/etc/tasks/imports/imported.xml");
      $f2 = new PhingFile(PHING_TEST_BASE . "/etc/tasks/imports");
      
      $this->executeTarget("imported");
      $this->assertInLogs("phing.file.imported=" . $f1->getAbsolutePath());
      $this->assertInLogs("imported.basedir=" . $f2->getAbsolutePath());
    }

    public function testImported2Target () {
      $f1 = new PhingFile(PHING_TEST_BASE . "/etc/tasks/imports/importedImport.xml");
      
      $this->executeTarget("imported2");
      $this->assertInLogs("This is " . $f1->getAbsolutePath() . " imported2 target.");
    }
        
    public function testCascadeTarget () {
      $f1 = new PhingFile(PHING_TEST_BASE . "/etc/tasks/imports/imported.xml");
      $f2 = new PhingFile(PHING_TEST_BASE . "/etc/tasks/importing.xml");
      
      $this->executeTarget("cascade");
      $this->assertInLogs("This comes from the imported.properties file");
      $this->assertInLogs("This is " . $f1->getAbsolutePath() . " main target.");
      $this->assertInLogs("This is " . $f2->getAbsolutePath() . " cascade target.");
    }

    public function testFlipFlopTarget () {
      // calls target in main that depends on target in import that depends on 
      // target orverridden in main
      $this->executeTarget("flipflop");
      $f1 = new PhingFile(PHING_TEST_BASE . "/etc/tasks/importing.xml");
      $f2 = new PhingFile(PHING_TEST_BASE . "/etc/tasks/imports/imported.xml");
      $this->assertInLogs("This is " . $f1->getAbsolutePath() . " flop target.");
      $this->assertInLogs("This is " . $f2->getAbsolutePath() . " flip target.");
      $this->assertInLogs("This is " . $f1->getAbsolutePath() . " flipflop target.");

    }
}
