<?php

/*
 *  $Id: 516756889e12b6a1a07402ed14e839ecdad742b2 $
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

require_once 'phing/system/util/Register.php';

/**
 * Unit test for RegisterSlot
 *
 * @author Michiel Rook <mrook@php.net>
 * @package phing.system.util
 */
class RegisterSlotTest extends PHPUnit_Framework_TestCase
{
    private $slot = null;
    
    public function setUp()
    {
        $this->slot = new RegisterSlot('key123');
    }
    
    public function tearDown()
    {
        unset($this->slot);
    }
    
    public function testToString()
    {
        $this->slot->setValue('test123');
        
        $this->assertEquals((string) $this->slot, 'test123');
    }
    
    public function testArrayToString()
    { 
        $this->slot->setValue(array('test1','test2','test3'));
        
        $this->assertEquals((string) $this->slot, '{test1,test2,test3}');
    }

    public function testMultiArrayToString()
    { 
        $this->slot->setValue(array('test1','test2',array('test4','test5',array('test6','test7')),'test3'));
        
        $this->assertEquals((string) $this->slot, '{test1,test2,{test4,test5,{test6,test7}},test3}');
    }
}
