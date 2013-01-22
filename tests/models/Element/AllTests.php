<?php
//require_once 'PHPUnit/Framework.php';


class Element_AllTests extends PHPUnit_Framework_TestSuite {



    public static function suite() {
        $suite = new Element_AllTests('ElementTests');
        $suite->addTestSuite('Element_ClassTest');
        $suite->addTestSuite('Element_ElementCreateTest');
        $suite->addTestSuite('Element_LazyLoadingTest');
        $suite->addTestSuite('Element_CopyAndDeleteTest');
        return $suite;
    }
}
