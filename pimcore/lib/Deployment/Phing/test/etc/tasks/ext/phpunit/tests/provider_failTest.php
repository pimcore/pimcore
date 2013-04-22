<?php

class provider_failTest extends PHPUnit_Framework_TestCase {
 public function test_simplefail() {
  $this->assertFalse(true);
 }
 /**
  * @dataProvider provider
  */
 public function test_provider($v1,$v2) {
  $this->assertEquals($v1,$v2);
 }

 public function provider() {
  return array(
   array(true,true)
  );
 }
}
