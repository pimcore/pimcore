<?php
/**
 * Created by JetBrains PhpStorm.
 * User: jaichhorn
 * Date: 31.01.13
 * Time: 09:33
 * To change this template use File | Settings | File Templates.
 */
class Test_SuiteBase extends PHPUnit_Framework_TestSuite
{


    protected function setUp() {

    }

    protected function tearDown() {
        Test_Tool::cleanUp();
    }


}
