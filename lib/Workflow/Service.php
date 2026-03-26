<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Workflow;

use DateTime;
use Pimcore\Logger;
use Pimcore\Model\Element;
use Pimcore\Model\User;

class Service
{
    /**
     * @param array $fc - The field configuration from the Workflow
     * @param mixed $value - The value
     *
     */
    public static function createNoteData(array $fc, mixed $value): array
    {
        $data = [];

        //supported types for notes are text, date, document, asset, object, bool
        if ($fc['fieldType'] === 'checkbox') {
            $data['type'] = 'bool';
            $data['value'] = (bool) $value;
        } elseif (in_array($fc['fieldType'], ['date', 'datetime'])) {
            $data['type'] = 'date';

            $dateTime = new DateTime();

            if (empty($fc['timeformat']) || $fc['timeformat'] === 'milliseconds') {
                $numericValue = is_numeric($value) ? $value : $dateTime->getTimestamp();
                $dateTime->setTimestamp(intval($numericValue / 1000));
            } elseif (is_numeric($value)) {
                $dateTime->setTimestamp($value);
            }
            $data['value'] = $dateTime;
            /**
            } elseif (false) { //TODO

                $data['type'] = 'document';
                $data['value'] = $value;
            } elseif (false) { //TODO

                $data['type'] = 'asset';
                $data['value'] = $value;
            } elseif (false) { //TODO

                $data['type'] = 'object';
                $data['value'] = $value;
            */
        } else {
            $data['type'] = 'text';
            $data['value'] = $value;
        }

        $data['key'] = $fc['name'];

        return $data;
    }

    public static function getDataFromEditmode(mixed $data, string $pimcoreTagName): mixed
    {
        $tagClass = '\\Pimcore\\Model\\DataObject\\ClassDefinition\\Data\\' . ucfirst($pimcoreTagName);
        if (\Pimcore\Tool::classExists($tagClass)) {
            /**
             * @var \Pimcore\Model\DataObject\ClassDefinition\Data $tag
             */
            $tag = new $tagClass();

            return $tag->getDataFromEditmode($data);
        }

        //purposely return null if there is no valid class, log a warning
        Logger::warning("No valid pimcore tag found for fieldType ({$pimcoreTagName}), check 'fieldType' exists, and 'type' is not being used in config");

        return null;
    }

    /**
     * Creates a note for an action with a transition
     *
     *
     * @return Element\Note $note
     */
    public static function createActionNote(Element\ElementInterface $element, string $type, string $title, string $description, array $noteData, ?User $user = null): Element\Note
    {
        //prepare some vars for creating the note
        if (!$user) {
            $user = \Pimcore\Tool\Admin::getCurrentUser();
        }

        $note = new Element\Note();
        $note->setElement($element);
        $note->setDate(time());
        $note->setType($type);
        $note->setTitle($title);
        $note->setDescription($description);
        $note->setUser($user ? $user->getId() : 0);

        foreach ($noteData as $row) {
            if ($row['key'] === 'noteDate' && $row['type'] === 'date') {
                /**
                 * @var DateTime $date
                 */
                $date = $row['value'];
                $note->setDate($date->getTimestamp());
            } else {
                $note->addData($row['key'], $row['type'], $row['value']);
            }
        }

        $note->save();

        return $note;
    }
}
