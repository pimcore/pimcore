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
        // we have to run the migrations of Version20200226102938 here, otherwise saving the documents will fail
        if ($schema->getTable('dependencies')->hasIndex('sourceid')) {
            $this->addSql('ALTER TABLE `dependencies`
                DROP INDEX `sourceid`,
                DROP INDEX `targetid`,
                DROP INDEX `targettype`,
                DROP PRIMARY KEY
            ');

            $this->addSql('ALTER TABLE `dependencies`
                ADD COLUMN `id` BIGINT NOT NULL AUTO_INCREMENT FIRST,
                ADD UNIQUE INDEX `combi` (`sourcetype`, `sourceid`, `targettype`, `targetid`),
                ADD INDEX `targettype_targetid` (`targettype`, `targetid`),
                ADD PRIMARY KEY (`id`);
            ');
        }
    }

    /**
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
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
                        $document->save(['versionNote' => 'Migration: 20191114123638']);
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
