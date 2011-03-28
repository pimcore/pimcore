<?php
require_once 'PHPUnit/Framework.php';


class AllTests extends PHPUnit_Framework_TestSuite {


    /**
     * legacy - not required anymore
     *
     *
     * @return void
     */
    protected function cleanUp() {

        try {
            $class = Object_Class::getByName("unittest");
            if ($class instanceof Object_Class) {
                $class->delete();
            }
        } catch (Exception $e) {
        }



        try {
            $objectRoot = Object_Abstract::getById(1);
            if ($objectRoot and $objectRoot->hasChilds()) {
                $childs = $objectRoot->getChilds();
                foreach ($childs as $child) {
                    $child->delete();
                }
            }
        } catch (Exception $e) {
        }

        try {
            $assetRoot = Asset::getById(1);
            if ($assetRoot and $assetRoot->hasChilds()) {
                $childs = $assetRoot->getChilds();
                foreach ($childs as $child) {
                    $child->delete();
                }
            }
        } catch (Exception $e) {
        }

        try {
            $documentRoot = Asset::getById(1);
            if ($documentRoot and $documentRoot->hasChilds()) {
                $childs = $documentRoot->getChilds();
                foreach ($childs as $child) {
                    $child->delete();
                }
            }
        } catch (Exception $e) {
        }


        try{
            $userList = new User_List();
            $userList->setCondition("id > 1");
            $users = $userList->load();
            if(is_array($users) and count($users)>0){
                foreach($users as $user){
                    $user->delete();
                }
            }
        } catch (Exception $e) {

        }

    }

    protected function setUp() {

        $this->cleanUp();
    }

    protected function tearDown() {
        //$this->cleanUp();
    }

    public static function suite() {
        $suite = new AllTests('Models');

        $suite->addTest(User_AllTests::suite());
        $suite->addTest(Element_AllTests::suite());
        $suite->addTest(Webservice_AllTests::suite());


        return $suite;
    }
}

?>
