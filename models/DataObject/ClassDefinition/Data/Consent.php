<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore;
use Pimcore\DataObject\Consent\Service;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\StructuredValidationException;
use Pimcore\Model\Element\ValidationMessageKey;
use Pimcore\Normalizer\NormalizerInterface;

class Consent extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    use DataObject\Traits\DataWidthTrait;

    /**
     * @internal
     *
     */
    public int $defaultValue = 0;

    /**
     * @param null|DataObject\Concrete $object
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     */
    public function getDataForResource(mixed $data, ?Concrete $object = null, array $params = []): array
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
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     */
    public function getDataFromResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): DataObject\Data\Consent
    {
        if (is_array($data) && $data[$this->getName() . '__consent'] !== null) {
            $consent = new DataObject\Data\Consent((bool) $data[$this->getName() . '__consent'], $data[$this->getName() . '__note']);
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
     *
     *
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): bool
    {
        if ($data instanceof DataObject\Data\Consent) {
            return $data->getConsent();
        }

        return false;
    }

    /**
     *
     *
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?array
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
     * The previous value decides whether this save writes an audit note, so a container that holds
     * this field somewhere a getter on the object cannot reach - a classification store key, whose
     * name is a label and not a class field - hands it over as `$params['oldData']`. Only the
     * previous value is taken from there; the note is still written against the object.
     *
     * @see Data::getDataFromEditmode
     */
    public function getDataFromEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): DataObject\Data\Consent
    {
        if ($data === 'false') {
            $data = false;
        }

        $oldData = $this->resolvePreviousConsent($object, $params);
        $noteId = null;

        if (!$oldData || $oldData->getConsent() != $data) {
            // without an object there is nothing to write the note against, so the value is
            // converted and the note is left out rather than the conversion failing
            if ($object !== null) {
                $service = Pimcore::getContainer()->get(Service::class);

                if ($data == true) {
                    $note = $service->insertConsentNote(
                        $object,
                        $this->getName(),
                        'Manually by User via Pimcore Backend.'
                    );
                } else {
                    $note = $service->insertRevokeNote($object, $this->getName());
                }
                $noteId = $note->getId();
            }
        } else {
            $noteId = $oldData->getNoteId();
        }

        return new DataObject\Data\Consent($data, $noteId);
    }

    /**
     * What this field held before the payload being converted was sent.
     *
     * A container that hands the previous value over answers for it completely: `oldData` is
     * present and null exactly when the place this field sits in holds nothing - an empty
     * classification store key, {@see Classificationstore::getDataFromEditmode()}, which always
     * sets the member. Falling through to the object's getter on that null would read
     * the class field that happens to carry the same name as the key, and an equal payload would
     * then reuse that unrelated field's note id and write no audit note for the key at all. The
     * getter is for the case the member is absent: a caller that knows nothing of this parameter,
     * which is every caller the object's own save path has.
     *
     * @param array<string, mixed> $params
     */
    private function resolvePreviousConsent(?DataObject\Concrete $object, array $params): ?DataObject\Data\Consent
    {
        if (array_key_exists('oldData', $params)) {
            $fromContainer = $params['oldData'];

            return $fromContainer instanceof DataObject\Data\Consent ? $fromContainer : null;
        }

        if ($object === null) {
            return null;
        }

        $getter = 'get' . ucfirst($this->getName());
        if (!method_exists($object, $getter)) {
            return null;
        }

        $oldData = $object->$getter();

        return $oldData instanceof DataObject\Data\Consent ? $oldData : null;
    }

    /** Converts the data sent from the object merger plugin back to the internal object. Similar to
     * getDiffDataForEditMode() an array of data elements is passed in containing the following attributes:
     *  - "field" => the name of (this) field
     *  - "key" => the key of the data element
     *  - "data" => the data
     */
    public function getDiffDataFromEditmode(array $data, ?DataObject\Concrete $object = null, array $params = []): DataObject\Data\Consent
    {
        $data = $data[0]['data'];

        $consent = false;
        if (isset($data['consent'])) {
            $consent = $data['consent'];
        }

        $service = Pimcore::getContainer()->get(Service::class);

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
     * @param DataObject\Concrete|null $object
     *
     */
    public function getDataForGrid(?DataObject\Data\Consent $data, ?Concrete $object = null, array $params = []): ?array
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     * @param DataObject\Concrete|null $object
     *
     */
    public function getDataFromGridEditor(bool|string $data, ?Concrete $object = null, array $params = []): DataObject\Data\Consent
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     *
     *
     * @see Data::getVersionPreview
     *
     */
    public function getVersionPreview(mixed $data, ?DataObject\Concrete $object = null, array $params = []): string
    {
        return $data ? (string)$data->getConsent() : '';
    }

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if (!$omitMandatoryCheck && $this->getMandatory() && $data === null) {
            throw (new StructuredValidationException('Empty mandatory field [ ' . $this->getName() . ' ]'))
                ->setTranslation(ValidationMessageKey::MANDATORY);
        }

        /* @todo seems to cause problems with old installations
        if(!is_bool($data) and $data !== 1 and $data !== 0){
        throw new \Exception(get_class($this).": invalid data");
        }*/
    }

    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params);

        return $data ? (string)$data->getConsent() : '';
    }

    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    /**
     * @param DataObject\ClassDefinition\Data\Consent $mainDefinition
     */
    public function synchronizeWithMainDefinition(DataObject\ClassDefinition\Data $mainDefinition): void
    {
        $this->defaultValue = $mainDefinition->defaultValue;
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     *
     *
     *
     */
    public function getFilterCondition(mixed $value, string $operator, array $params = []): string
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
     * @param array $params optional params used to change the behavior
     *
     */
    public function getFilterConditionExt(mixed $value, string $operator, array $params = []): string
    {
        $db = \Pimcore\Db::get();
        $value = $db->quote((string) $value);
        $key = $db->quoteIdentifier($this->name);

        // brickPrefix arrives ready to concatenate - already quoted where it needs to be and
        // already carrying its trailing dot - so it must not be quoted again here.
        $brickPrefix = !empty($params['brickPrefix']) ? $params['brickPrefix'] : '';

        return 'IFNULL(' . $brickPrefix . $key . ', 0) = ' . $value . ' ';
    }

    public function getDataForSearchIndex(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        return '';
    }

    public function supportsInheritance(): bool
    {
        return false;
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        $oldValue = $oldValue instanceof DataObject\Data\Consent ? $oldValue->getConsent() : null;
        $newValue = $newValue instanceof DataObject\Data\Consent ? $newValue->getConsent() : null;

        return $oldValue === $newValue;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\Consent::class;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\Consent::class;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . DataObject\Data\Consent::class . '|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . DataObject\Data\Consent::class . '|null';
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        if ($value instanceof DataObject\Data\Consent) {
            return [
                'consent' => $value->getConsent(),
                'noteId' => $value->getNoteId(),
            ];
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): ?DataObject\Data\Consent
    {
        if (is_array($value)) {
            return new DataObject\Data\Consent($value['consent'], $value['noteId']);
        }

        return null;
    }

    public function getColumnType(): array
    {
        return [
            'consent' => 'tinyint(1)',
            'note' => 'int(11)',
        ];
    }

    public function getQueryColumnType(): string
    {
        return 'tinyint(1)';
    }

    public function getFieldType(): string
    {
        return 'consent';
    }
}
