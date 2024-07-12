<?php

declare(strict_types=1);

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\DataObject\Consent;

use Exception;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Data\Consent;
use Pimcore\Model\Element\Note;

class Service
{
    /**
     * Inserts note for consent based to give object.
     *
     * @param AbstractObject $object - object to attach the note to
     * @param string $fieldname - fieldname of consent field
     * @param string $consentContent - message that should be stored into the notes description
     * @param array $metaData - array of key/values that should be attached as details to the note
     *
     */
    public function insertConsentNote(AbstractObject $object, string $fieldname, string $consentContent, array $metaData = []): Note
    {
        $note = new Note();
        $note->setCid($object->getId());
        $note->setCtype('object');
        $note->setType('consent-given');
        $note->setTitle('Consent given for field ' . $fieldname);
        $note->setDate(time());
        $note->setDescription($consentContent);

        foreach ($metaData as $key => $data) {
            $note->addData($key, 'text', $data);
        }
        $note->save();

        return $note;
    }

    /**
     * Inserts note for revoke based to give object.
     *
     * @param AbstractObject $object - object to attach the note to
     * @param string $fieldname - fieldname of consent field
     *
     */
    public function insertRevokeNote(AbstractObject $object, string $fieldname): Note
    {
        $note = new Note();
        $note->setCid($object->getId());
        $note->setCtype('object');
        $note->setType('consent-revoked');
        $note->setTitle('Consent revoked for field ' . $fieldname);
        $note->setDate(time());
        $note->save();

        return $note;
    }

    /**
     * Give consent to given fieldname - sets field value and adds note
     *
     * @param AbstractObject $object - object to set the consent to
     * @param string $fieldname - fieldname of consent field
     * @param string $consentContent - message that should be stored into the notes description
     * @param array $metaData - array of key/values that should be attached as details to the note
     *
     * @throws Exception
     */
    public function giveConsent(AbstractObject $object, string $fieldname, string $consentContent, array $metaData = []): Note
    {
        $setter = 'set' . ucfirst($fieldname);
        if (!method_exists($object, $setter)) {
            throw new Exception("Method $setter does not exist in given object.");
        }

        $note = $this->insertConsentNote($object, $fieldname, $consentContent, $metaData);

        $object->$setter(new Consent(true, $note->getId()));
        $object->save();

        return $note;
    }

    /**
     * Revoke consent to given fieldname - sets field value and adds note
     *
     * @param AbstractObject $object - object to revoke the consent from
     * @param string $fieldname - fieldname of consent field
     *
     * @throws Exception
     */
    public function revokeConsent(AbstractObject $object, string $fieldname): Note
    {
        $setter = 'set' . ucfirst($fieldname);
        if (!method_exists($object, $setter)) {
            throw new Exception("Method $setter does not exist in given object.");
        }

        $note = $this->insertRevokeNote($object, $fieldname);

        $object->$setter(new Consent(false, $note->getId()));
        $object->save();

        return $note;
    }
}
