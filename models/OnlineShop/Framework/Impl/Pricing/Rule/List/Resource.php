<?php

class OnlineShop_Framework_Impl_Pricing_Rule_List_Resource extends \Pimcore\Model\Listing\Resource\AbstractResource
{
    /**
     * @var string
     */
    protected $ruleClass = 'OnlineShop_Framework_Impl_Pricing_Rule';

    /**
     * @return array
     */
    public function load() {
        $rules = array();

        // load objects
        $ruleIds = $this->db->fetchCol("SELECT id FROM " . OnlineShop_Framework_Impl_Pricing_Rule_Resource::TABLE_NAME .
                                                 $this->getCondition() . $this->getOrder() . $this->getOffsetLimit());

        foreach ($ruleIds as $id)
            $rules[] = call_user_func(array($this->getRuleClass(), 'getById'), $id);

        $this->model->setRules($rules);

        return $rules;
    }

    public function setRuleClass($cartClass)
    {
        $this->ruleClass = $cartClass;
    }

    public function getRuleClass()
    {
        return $this->ruleClass;
    }

}