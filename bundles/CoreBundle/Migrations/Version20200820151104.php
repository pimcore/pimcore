<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;
use Pimcore\Model\Element\Tag;

class Version20200820151104 extends AbstractPimcoreMigration
{
    public function doesSqlMigrations(): bool
    {
        return true;
    }

    private function renameDuplicateTags($tags)
    {
        $skipIds = [];
        /** @var Tag $tag */
        foreach ($tags as $tag) {
            if (!in_array($tag->getId(), $skipIds)) {
                $duplicateTags = new Tag\Listing();
                $duplicateTags->setCondition('name = ? AND parentId = ? AND id != ?', [$tag->getName(), $tag->getParentId(), $tag->getId()]);

                $i = 0;
                foreach ($duplicateTags as $duplicateTag) {
                    $i++;
                    try {
                        $oldName = $duplicateTag->getName();
                        $newName = $duplicateTag->getName() . '_' . $i;
                        $duplicateTag->setName($newName);
                        $duplicateTag->save();

                        $this->writeMessage('<info>Attention: </info>' . sprintf('Tag(ID: %d): "%s" renamed to "%s"', $duplicateTag->getId(), $oldName, $newName));
                        $skipIds[] = $duplicateTag->getId();
                    } catch (\Exception $e) {
                        $this->writeMessage('An error occurred while renaming tags for table migrations: ' . $e->getMessage());
                    }
                }
            }

            if ($tag->getChildren()) {
                $this->renameDuplicateTags($tag->getChildren());
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        try {
            //correct duplicate tags on same level
            $tags = new Tag\Listing();
            $tags->setCondition('parentId = 0');
            $this->renameDuplicateTags($tags);

            //add composite index for tags uniqueness at same level
            $this->addSql('ALTER TABLE `tags` CHANGE COLUMN `name` `name` varchar(255) DEFAULT NULL COLLATE utf8_bin AFTER `idPath`');
            $this->addSql('ALTER TABLE `tags` ADD UNIQUE INDEX `idPath_name` (`idPath`,`name`)');
        } catch (\Exception $e) {
            $this->writeMessage('An error occurred while performing migrations: ' . $e->getMessage());
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        try {
            $this->addSql('ALTER TABLE `tags` CHANGE COLUMN `name` `name` varchar(255) DEFAULT NULL AFTER `idPath`');
            $this->addSql('ALTER TABLE `tags` DROP INDEX `idPath_name`');
        } catch (\Exception $e) {
            $this->writeMessage('An error occurred while performing migrations: ' . $e->getMessage());
        }
    }
}
