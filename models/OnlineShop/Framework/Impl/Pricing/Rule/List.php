<?php

class OnlineShop_Framework_Impl_Pricing_Rule_List extends Pimcore_Model_List_Abstract {

    /**
     * @var array|OnlineShop_Framework_Pricing_IRule
     */
    protected $rules;

    /**
     * @var boolean
     */
    protected $validate;


    /**
     * @param bool $state
     */
    public function setValidation($state)
    {
        $this->validate = (bool)$state;
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function isValidOrderKey($key)
    {
        return in_array($key, array('prio', 'name'));
    }

    /**
     * @return array
     */
    function getRules()
    {
        // load rules if not loaded yet
        if(empty($this->rules))
            $this->load();

        return $this->rules;
    }

    /**
     * @param array $rules
     * @return void
     */
    function setRules(array $rules)
    {
        $this->rules = $rules;
    }

}
