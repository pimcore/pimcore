<?php
/**
 * Created by PhpStorm.
 * User: tballmann
 * Date: 09.01.2015
 * Time: 12:55
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\ElasticSearch;
use \Pimcore\Logger;
use \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\IProductList;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\IRelationInterpreter;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IIndexable;

/**
 *  Use this for ES Version >= 6
 */
class DefaultElasticSearch6 extends AbstractElasticSearch
{

}
