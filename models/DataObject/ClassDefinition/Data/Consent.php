<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\DataObject\Consent\Service;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Normalizer\NormalizerInterface;

class Consent extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    use Extension\ColumnType;
    use Extension\QueryColumnType;

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'consent';

    /**
     * @internal
     *
     * @var int
     */
    public $defaultValue = 0;

    /**
     * Type for the column to query
     *
     * @internal
     *
     * @var string
     */
    public $queryColumnType = 'tinyint(1)';

    /**
     * Type for the column
     *
     * @internal
     *
     * @var array
     */
    public $columnType = [
        'consent' => 'tinyint(1)',
        'note' => 'int(11)',
    ];

    /**
     * Width of field
     *
     * @internal
     *
     * @var string|int
     */
    public $width = 0;

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param DataObject\Data\Consent|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data instanceof DataObject\Data\Consent) {
            return [
                $this->getName() . '__consent' => $data->getConsent(),
                $this->getName() . '__note' => $data->getNoteId(),
            ];
        }

        return [
            $this->getName() . '__consent' => false,
            $this->getName() . '__note' => null,
        ];
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param array $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return DataObject\Data\Consent
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if (is_array($data) && $data[$this->getName() . '__consent'] !== null) {
            $consent = new DataObject\Data\Consent($data[$this->getName() . '__consent'], $data[$this->getName() . '__note']);
        } else {
            $consent = new DataObject\Data\Consent();
        }

        if (isset($params['owner'])) {
            $consent->_setOwner($params['owner']);
            $consent->_setOwnerFieldname($params['fieldname']);
            $consent->_setOwnerLanguage($params['language'] ?? null);
        }

        return $consent;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param DataObject\Data\Consent|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return bool
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        if ($data instanceof DataObject\Data\Consent) {
            return $data->getConsent();
        }

        return false;
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param DataObject\Data\Consent|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        // get data & info from note
        if ($data instanceof DataObject\Data\Consent) {
            return [
                'consent' => $data->getConsent(),
                'noteContent' => $data->getSummaryString(),
                'noteId' => $data->getNoteId(),
            ];
        }

        return null;
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param string|bool $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return DataObject\Data\Consent
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        if ($data === 'false') {
            $data = false;
        }

        /** @var DataObject\Data\Consent $oldData */
        $oldData = null;
        $noteId = null;

        $getter = 'get' . ucfirst($this->getName());
        if (method_exists($object, $getter)) {
            $oldData = $object->$getter();
        }

        if (!$oldData || $oldData->getConsent() != $data) {
            $service = \Pimcore::getContainer()->get(Service::class);

            if ($data == true) {
                $note = $service->insertConsentNote($object, $this->getName(), 'Manually by User via Pimcore Backend.');
            } else {
                $note = $service->insertRevokeNote($object, $this->getName());
            }
            $noteId = $note->getId();
        }

        return new DataObject\Data\Consent($data, $noteId);
    }

    /** Converts the data sent from the object merger plugin back to the internal object. Similar to
     * getDiffDataForEditMode() an array of data elements is passed in containing the following attributes:
     *  - "field" => the name of (this) field
     *  - "key" => the key of the data element
     *  - "data" => the data
     *
     * @param array $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getDiffDataFromEditmode($data, $object = null, $params = [])
    {
        $data = $data[0]['data'];

        $consent = false;
        if (isset($data['consent'])) {
            $consent = $data['consent'];
        }

        $service = \Pimcore::getContainer()->get(Service::class);

        $originalNote = null;
        if (!empty($data['noteId'])) {
            $originalNote = Model\Element\Note::getById($data['noteId']);
        }

        $noteId = null;
        if (!$originalNote || ($originalNote->getCtype() == 'object' && $originalNote->getCid() != $object->getId())) {
            if ($consent == true) {
                $note = $service->insertConsentNote($object, $this->getName(), $data['noteContent']);
            } else {
                $note = $service->insertRevokeNote($object, $this->getName());
            }

            if (!empty($originalNote)) {
                $note->setTitle($note->getTitle() . ' (objects merged - original consent date: ' . date('Y-m-d H:i:s', $originalNote->getDate()) .')');
                $note->save();

                $noteId = $note->getId();
            }
        } else {
            $noteId = $originalNote->getId();
        }

        return new DataObject\Data\Consent($consent, $noteId);
    }

    /**
     * @param DataObject\Data\Consent|null $data
     * @param DataObject\Concrete|null $object
     * @param array $params
     *
     * @return array|null
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     * @param string|bool $data
     * @param DataObject\Concrete|null $object
     * @param array $params
     *
     * @return DataObject\Data\Consent
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param DataObject\Data\Consent|null $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        return $data ? (string)$data->getConsent() : '';
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = [])
    {
        if (!$omitMandatoryCheck and $this->getMandatory() and $data === null) {
            throw new Model\Element\ValidationException('Empty mandatory field [ ' . $this->getName() . ' ]');
        }

        /* @todo seems to cause problems with old installations
        if(!is_bool($data) and $data !== 1 and $data !== 0){
        throw new \Exception(get_class($this).": invalid data");
        }*/
    }

    /**
     * {@inheritdoc}
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);

        return $data ? (string)$data->getConsent() : '';
    }

    /**
     * {@inheritdoc}
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /**
     * @param DataObject\ClassDefinition\Data\Consent $masterDefinition
     */
    public function synchronizeWithMasterDefinition(DataObject\ClassDefinition\Data $masterDefinition)
    {
        $this->defaultValue = $masterDefinition->defaultValue;
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     *
     * @param  string$value
     * @param  string $operator
     * @param  array $params
     *
     * @return string
     *
     */
    public function getFilterCondition($value, $operator, $params = [])
    {
        $params['name'] = $this->name;

        return $this->getFilterConditionExt(
            $value,
            $operator,
            $params
        );
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     *
     * @param string $value
     * @param string $operator
     * @param array $params optional params used to change the behavior
     *
     * @return string
     */
    public function getFilterConditionExt($value, $operator, $params = [])
    {
        $db = \Pimcore\Db::get();
        $value = $db->quote($value);
        $key = $db->quoteIdentifier($this->name);

        $brickPrefix = $params['brickPrefix'] ? $db->quoteIdentifier($params['brickPrefix']) . '.' : '';

        return 'IFNULL(' . $brickPrefix . $key . ', 0) = ' . $value . ' ';
    }

    /**
     * {@inheritdoc}
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        return '';
    }

    /**
     * @return string|int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param string|int $width
     */
    public function setWidth($width)
    {
        if (is_numeric($width)) {
            $width = (int)$width;
        }
        $this->width = $width;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsInheritance()
    {
        return false;
    }

    /**
     * @param DataObject\Data\Consent|null $oldValue
     * @param DataObject\Data\Consent|null $newValue
     *
     * @return bool
     */
    public function isEqual($oldValue, $newValue): bool
    {
        $oldValue = $oldValue instanceof DataObject\Data\Consent ? $oldValue->getConsent() : null;
        $newValue = $newValue instanceof DataObject\Data\Consent ? $newValue->getConsent() : null;

        return $oldValue === $newValue;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\Consent::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\Consent::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocInputType(): ?string
    {
        return '\\' . DataObject\Data\Consent::class . '|null';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocReturnType(): ?string
    {
        return '\\' . DataObject\Data\Consent::class . '|null';
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($value, $params = [])
    {
        if ($value instanceof DataObject\Data\Consent) {
            return [
                'consent' => $value->getConsent(),
                'noteId' => $value->getNoteId(),
            ];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($value, $params = [])
    {
        if (is_array($value)) {
            return new DataObject\Data\Consent($value['consent'], $value['noteId']);
        }

        return null;
    }
}
