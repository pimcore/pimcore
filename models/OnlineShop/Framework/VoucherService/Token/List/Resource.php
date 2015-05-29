<?php

class OnlineShop_Framework_VoucherService_Token_List_Resource extends \Pimcore\Model\Listing\Resource\AbstractResource
{

    public function load()
    {
        $tokens = array();

        $unitIds = $this->db->fetchAll("SELECT * FROM " .
            OnlineShop_Framework_VoucherService_Token_Resource::TABLE_NAME .
            $this->getCondition() .
            $this->getOrder() .
            $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($unitIds as $row) {
            $item = new OnlineShop_Framework_VoucherService_Token();
            $item->getResource()->assignVariablesToModel($row);
            $tokens[] = $item;
        }

        $this->model->setTokens($tokens);

        return $tokens;
    }

    public function getTotalCount()
    {
        try {
            $amount = (int)$this->db->fetchOne("SELECT COUNT(*) as amount FROM " .
                OnlineShop_Framework_VoucherService_Token_Resource::TABLE_NAME .
                $this->getCondition(),
                $this->model->getConditionVariables());
        } catch (\Exception $e) {

        }

        return $amount;
    }

}