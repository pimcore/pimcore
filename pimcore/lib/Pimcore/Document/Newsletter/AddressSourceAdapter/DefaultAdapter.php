<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Document\Newsletter\AddressSourceAdapter;

use Pimcore\Document\Newsletter\AddressSourceAdapterInterface;
use Pimcore\Document\Newsletter\SendingParamContainer;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Listing;

class DefaultAdapter implements AddressSourceAdapterInterface
{
    /**
     * @var string
     */
    protected $class;

    /**
     * @var string
     */
    protected $condition;

    /**
     * @var int[]
     */
    protected $personas;

    /**
     * @var int
     */
    protected $elementsTotal;

    /**
     * @var Listing
     */
    protected $list;

    /**
     * IAddressSourceAdapter constructor.
     *
     * @param $params
     */
    public function __construct($params)
    {
        $this->class = $params['class'];
        $this->condition = empty($params['condition']) ? $params['objectFilterSQL'] : $params['condition'];
        $this->personas = $params['personas'];
    }

    /**
     * @return Listing
     */
    protected function getListing()
    {
        if (empty($this->list)) {
            $objectList = '\\Pimcore\\Model\\DataObject\\' . ucfirst($this->class) . '\\Listing';
            $this->list = new $objectList();

            $conditions = ['(newsletterActive = 1 AND newsletterConfirmed = 1)'];
            if ($this->condition) {
                $conditions[] = '(' . $this->condition . ')';
            }
            if ($this->personas) {
                $class = ClassDefinition::getByName($this->class);
                if ($class && $class->getFieldDefinition('persona')) {
                    $personas = [];

                    if ($class->getFieldDefinition('persona') instanceof \Pimcore\Model\DataObject\ClassDefinition\Data\Persona) {
                        foreach ($this->personas as $value) {
                            if (!empty($value)) {
                                $personas[] = $this->list->quote($value);
                            }
                        }
                        $conditions[] = 'persona IN (' . implode(',', $personas) . ')';
                    } elseif ($class->getFieldDefinition('persona') instanceof \Pimcore\Model\DataObject\ClassDefinition\Data\Personamultiselect) {
                        $personasCondition = [];
                        foreach ($this->personas as $value) {
                            $personasCondition[] = 'persona LIKE ' . $this->list->quote('%,' . $value .  ',%');
                        }
                        $conditions[] = '(' . implode(' OR ', $personasCondition). ')';
                    }
                }
            }

            $this->list->setCondition(implode(' AND ', $conditions));
            $this->list->setOrderKey('email');
            $this->list->setOrder('ASC');

            $this->elementsTotal = $this->list->getTotalCount();
        }

        return $this->list;
    }

    /**
     * returns array of email addresses for batch sending
     *
     * @return SendingParamContainer[]
     */
    public function getMailAddressesForBatchSending()
    {
        $listing = $this->getListing();
        $ids = $listing->loadIdList();

        $class = ClassDefinition::getByName($this->class);
        $tableName = 'object_' . $class->getId();

        $emails = [];

        if (count($ids) > 0) {
            $db = \Pimcore\Db::get();
            $emails = $db->fetchCol("SELECT email FROM $tableName WHERE o_id IN (" . implode(',', $ids) . ')');
        }

        $containers = [];
        foreach ($emails as $email) {
            $containers[] = new SendingParamContainer($email, ['emailAddress' => $email]);
        }

        return $containers;
    }

    /**
     * returns params to be set on mail for test sending
     *
     * @param string $emailAddress
     *
     * @return SendingParamContainer
     */
    public function getParamsForTestSending($emailAddress)
    {
        $listing = $this->getListing();
        $listing->setOrderKey('RAND()', false);
        $listing->setLimit(1);
        $listing->setOffset(0);

        $object = current($listing->load());

        return new SendingParamContainer($emailAddress, [
            'object' => $object
        ]);
    }

    /**
     * returns total number of newsletter recipients
     *
     * @return int
     */
    public function getTotalRecordCount()
    {
        $this->getListing();

        return $this->elementsTotal;
    }

    /**
     * returns array of params to be set on mail for single sending
     *
     * @param $limit
     * @param $offset
     *
     * @return SendingParamContainer[]
     */
    public function getParamsForSingleSending($limit, $offset)
    {
        $listing = $this->getListing();
        $listing->setLimit($limit);
        $listing->setOffset($offset);
        $objects = $listing->load();

        $containers = [];

        foreach ($objects as $object) {
            $containers[] = new SendingParamContainer($object->getEmail(), [
                'gender' => method_exists($object, 'getGender') ? $object->getGender() : '',
                'firstname' => method_exists($object, 'getFirstname') ? $object->getFirstname() : '',
                'lastname' => method_exists($object, 'getLastname') ? $object->getLastname() : '',
                'email' => $object->getEmail(),
                'token' => $object->getProperty('token'),
                'object' => $object
            ]);
        }

        return $containers;
    }
}
