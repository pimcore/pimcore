<?php

/*
 *  $Id: 6f50107c2b141d8fe3118952c9fc2289fd7781ca $
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
 * @author Michiel Rook <mrook@php.net>
 * @package phing.filters
 */
class ReplaceTokensWithFileTest extends BuildFileTest
{
    public function setUp()
    {
        $this->configureProject(PHING_TEST_BASE . "/etc/filters/ReplaceTokensWithFile/build.xml");
    }
    
    /**
     * Inspired by ticket #798 - http://www.phing.info/trac/ticket/798
     */
    public function testPostfix()
    {
        $this->executeTarget(__FUNCTION__);
        
        $this->assertInLogs('[filter:ReplaceTokensWithFile] Replaced "#!testReplace##" with content from file "testReplace.tpl"');
    }
}
