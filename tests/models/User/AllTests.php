<?php
require_once 'PHPUnit/Framework.php';



class User_AllTests extends PHPUnit_Framework_TestSuite {
    
    public static function suite() {
        $suite = new User_AllTests('UserTests');
        $suite->addTestSuite('User_UserTest');

        return $suite;
    }
}
?>