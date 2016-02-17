<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 10.04.13
 * Time: 09:11
 * To change this template use File | Settings | File Templates.
 */

class OnlineShop_Framework_Pricing_Condition_DateRange_Test extends Test_Base
{
    /**
     * @var \OnlineShop\Framework\PricingManager\IEnvironment
     */
    protected $environment;

    /**
     * setup test
     */
    public function setUp()
    {
//        Test_Tool::cleanUp();
        parent::setUp();

        set_include_path(get_include_path() . PATH_SEPARATOR . '/plugins/EcommerceFramework/www/plugins/OnlineShop/lib');

        $this->environment = new \OnlineShop\Framework\PricingManager\Environment;
    }


    /**
     * Verifies that a object with the same parent ID cannot be created.
     */
    public function testParentIdentical()
    {
        $dateRange = new \OnlineShop\Framework\PricingManager\Condition\DateRange();   // true
        $dateRange->setStarting(new Zend_Date('2013-02-03'));
        $dateRange->setEnding(new Zend_Date('2013-20-04'));
        $this->assertTrue($dateRange->check($this->environment));

//        $dateRange2 = new OnlineShop_Framework_Impl_Pricing_Condition_DateRange();  // false
//        $dateRange2->setStarting(new Zend_Date('2012-02-03'));
//        $dateRange2->setEnding(new Zend_Date('2012-30-04'));
    }

    /**
     * Parent ID of a new object cannot be 0
     */
    public function ___testParentIs0() {
        $this->printTestName();

        $savedObject = Test_Tool::createEmptyObject("", false);
        $this->assertTrue($savedObject->getId() == 0);

        $savedObject->setParentId(0);
        try {
            $savedObject->save();
            $this->fail("Expected an exception");
        } catch (Exception $e) {

        }
    }

}