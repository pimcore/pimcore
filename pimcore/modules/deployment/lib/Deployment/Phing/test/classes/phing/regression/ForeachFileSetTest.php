<?php

/*
 *  $Id: 3765840c01765a22ab08edcb14d63e94fb463606 $
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
 * Regression test for ticket http://www.phing.info/trac/ticket/252
 * - foreach on a fileset
 *
 * @package phing.regression
 */
class ForeachFileSetTest extends BuildFileTest { 
        
    public function setUp() { 
        $this->configureProject(PHING_TEST_BASE . "/etc/regression/252/build.xml");
    }

    public function testCustomTask () {
      $f = new PhingFile(PHING_TEST_BASE . "/etc/regression/252/build.xml");
      $this->executeTarget("main");
      $this->assertInLogs("Calling Buildfile '" . $f->getAbsolutePath() . "' with target 'subtask'");
    }
}
