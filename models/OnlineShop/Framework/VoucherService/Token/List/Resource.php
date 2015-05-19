<?php

class OnlineShop_Framework_VoucherService_Token_List_Resource extends \Pimcore\Model\Listing\Resource\AbstractResource
{

    public function load() {
        $configs = array();

        $unitIds = $this->db->fetchAll("SELECT token FROM " . Elements_OutputDataConfigToolkit_OutputDefinition_Resource::TABLE_NAME .
            $this->getCondition() . $this->getOrder() . $this->getOffsetLimit());

        foreach ($unitIds as $row) {
            $configs[] = OnlineShop_Framework_VoucherService_Token::getByCode($row['token']);
        }

        $this->model->setTokens($configs);

        return $configs;
    }

}