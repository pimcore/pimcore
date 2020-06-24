<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20181128074035 extends AbstractPimcoreMigration
{
    public function doesSqlMigrations(): bool
    {
        return false;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        /*
         * possible cases:
         *
            'mail@mail.com (Name)'
            'mail@mail.com (Name), mail2@mail.com (Name 2)'
            'mail@mail.com'
            'mail@mail.com, mail1@mail.com'
            'mail@mail.com (Name), mail1@mail.com'
            'mail1@mail.com, mail@mail.com (Name)'
        */
        $emailLogListing = new \Pimcore\Model\Tool\Email\Log\Listing();
        $fieldNames = ['From', 'To', 'Bcc', 'Cc', 'ReplyTo'];

        $i = 0;
        foreach ($emailLogListing->load() as $emailLogEntry) {
            $save = false;

            $i++;
            if ($i % 100 == 0) {
                \Pimcore::collectGarbage();
            }

            foreach ($fieldNames as $fieldName) {
                $save = $save | $this->migrateEmailField($fieldName, $emailLogEntry);
            }

            if ($save) {
                $emailLogEntry->save();
            }
        }
    }

    public function down(Schema $schema)
    {
        /*
         * possible cases:
         *
            'Name <mail@mail.com>'
            'Name <mail@mail.com>, Name 2 <mail2@mail.com>'
            'mail@mail.com'
            'mail@mail.com, mail1@mail.com'
            'Name <mail@mail.com>, mail1@mail.com'
            'mail1@mail.com, Name <mail@mail.com>'
        */

        $emailLogListing = new \Pimcore\Model\Tool\Email\Log\Listing();

        $fieldNames = ['From', 'To', 'Bcc', 'Cc', 'ReplyTo'];

        $i = 0;
        foreach ($emailLogListing->load() as $emailLogEntry) {
            $save = false;

            $i++;
            if ($i % 100 == 0) {
                \Pimcore::collectGarbage();
            }

            foreach ($fieldNames as $fieldName) {
                $save = $save | $this->downgradeEmailField($fieldName, $emailLogEntry);
            }

            if ($save) {
                $emailLogEntry->save();
            }
        }
    }

    private function migrateEmailField($fieldName, $emailLogEntry)
    {
        $save = false;
        $getter = 'get' . $fieldName;
        $setter = 'set' . $fieldName;

        $oldArray = $this->buildArrayFromOldFormat($emailLogEntry->{$getter}());
        if ($oldArray) {
            $newArray = [];
            foreach ($oldArray as $oldEntry) {
                $newEntry = '';
                if ($oldEntry['name']) {
                    $newEntry = $oldEntry['name'] . ' ' . '<' . $oldEntry['email'] . '>';
                    $save = true;
                } else {
                    $newEntry = $oldEntry['email'];
                }
                $newArray[] = $newEntry;
            }

            $emailLogEntry->{$setter}(implode(', ', $newArray));
        }

        return $save;
    }

    private function downgradeEmailField($fieldName, $emailLogEntry)
    {
        $save = false;

        $getter = 'get' . $fieldName;
        $setter = 'set' . $fieldName;

        $oldArray = \Pimcore\Helper\Mail::parseEmailAddressField($emailLogEntry->{$getter}());
        if ($oldArray) {
            $newArray = [];
            foreach ($oldArray as $oldEntry) {
                $newEntry = '';
                if ($oldEntry['name']) {
                    $newEntry = $oldEntry['email'] . ' ' . '(' . $oldEntry['name'] . ')';
                    $save = true;
                } else {
                    $newEntry = $oldEntry['email'];
                }
                $newArray[] = $newEntry;
            }

            $emailLogEntry->{$setter}(implode(', ', $newArray));
        }

        return $save;
    }

    /**
     * old format helper: mail@mail.com (Name)
     *
     * @param string $data
     *
     * @return array
     */
    protected function buildArrayFromOldFormat($data)
    {
        if (is_null($data)) {
            return [];
        }

        $dataArray = [];
        $tmp = explode(',', trim($data));

        foreach ($tmp as $entry) {
            if (!preg_match('/(.*)\<(.*)\>/', $entry)) {
                $tmp2 = explode(' ', trim($entry));
                $dataArray[] = [
                    'email' => trim($tmp2[0]),
                    'name' => str_replace(['(', ')'], '', $tmp2[1]),
                ];
            }
        }

        return $dataArray;
    }
}
