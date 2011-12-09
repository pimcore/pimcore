<?php
/**
 * Created by JetBrains PhpStorm.
 * User: kisi
 * Date: 20.11.11
 * Time: 07:07
 * To change this template use File | Settings | File Templates.
 */

class Pimcore_Placeholder_Text extends Pimcore_Placeholder_Abstract {

    public function getTestValue(){
        return '<span class="testValue">Test text</span>';
    }

    public function getReplacement(){
        return $this->getValue();
    }
}