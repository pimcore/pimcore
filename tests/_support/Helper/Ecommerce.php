<?php

namespace Pimcore\Tests\Helper;

use Codeception\Lib\ModuleContainer;
use Codeception\Module;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\VoucherToken;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tools\Installer;
use Pimcore\Model\DataObject\FilterDefinition;
use Pimcore\Model\DataObject\OfferToolCustomProduct;
use Pimcore\Model\DataObject\OfferToolOffer;
use Pimcore\Model\DataObject\OfferToolOfferItem;
use Pimcore\Model\DataObject\OnlineShopOrder;
use Pimcore\Model\DataObject\OnlineShopOrderItem;
use Pimcore\Model\DataObject\OnlineShopTaxClass;
use Pimcore\Model\DataObject\OnlineShopVoucherSeries;
use Pimcore\Tests\Util\Autoloader;

class Ecommerce extends Module
{
    /**
     * @inheritDoc
     */
    public function __construct(ModuleContainer $moduleContainer, $config = null)
    {
        $this->config = array_merge($this->config, [
            'run_installer' => true,
        ]);

        parent::__construct($moduleContainer, $config);
    }

    public function _beforeSuite($settings = [])
    {
        if ($this->config['run_installer']) {
            /** @var Pimcore $pimcoreModule */
            $pimcoreModule = $this->getModule('\\' . Pimcore::class);

            $this->debug('[ECOMMERCE] Running ecommerce framework installer');

            // install ecommerce framework
            $installer = $pimcoreModule->getContainer()->get(Installer::class);
            $installer->install();

            //explicitly load installed classes so that the new ones are used during tests
            Autoloader::load(OnlineShopTaxClass::class);
            Autoloader::load(FilterDefinition::class);
            Autoloader::load(OfferToolCustomProduct::class);
            Autoloader::load(OfferToolOfferItem::class);
            Autoloader::load(OfferToolOffer::class);
            Autoloader::load(OnlineShopOrderItem::class);
            Autoloader::load(OnlineShopOrder::class);
            Autoloader::load(OnlineShopVoucherSeries::class);
            Autoloader::load(VoucherToken::class);
        }
    }
}
