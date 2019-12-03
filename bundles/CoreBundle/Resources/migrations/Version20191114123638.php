<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20191114123638 extends AbstractPimcoreMigration
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
        //save all documents to update language property inheritance
        $listing = new \Pimcore\Model\Document\Listing();
        $listing->setCondition("id IN (SELECT cid FROM properties WHERE ctype='document' AND name='language')");
        foreach ($listing as $document) {
            $languageProperty = $document->getProperty('language', true);
            if ($languageProperty) {
                if ($document->getParent()) {
                    if ($document->getParent()->getProperty('language') == $languageProperty->getData()) {
                        $languageProperty->setInherited(true);
                        $document->save();
                    }
                }
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
