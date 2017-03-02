<?php
/**
 * Created by IntelliJ IDEA.
 * User: Michi
 * Date: 11.11.2010
 * Time: 10:35:07
 */


class TestSuite_Datatypes_KeyValueTest extends Test_Base
{
    public function testUninitialized()
    {
        $this->printTestName();
        $object = Test_Tool::createEmptyObject();
        $data = $object->getKeyvaluepairs();
        $fd = $object->getClass()->getFieldDefinition("keyvaluepairs");

        // make sure that this call does not bomb out
        $fd->getDiffDataForEditMode($data, $object);
    }
}
