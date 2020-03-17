<?php

namespace Pimcore\Bundle\CoreBundle\Migrations {

    use Doctrine\DBAL\Schema\Schema;
    use Pimcore\Migrations\Migration\AbstractPimcoreMigration;
    use Pimcore\Model\DataObject\ClassDefinition;

    class Version20200210101048 extends AbstractPimcoreMigration
    {
        public function doesSqlMigrations(): bool
        {
            return false;
        }

        /**
         * @param Schema $schema
         *
         * @throws \Exception
         */
        public function up(Schema $schema)
        {
            $list = new ClassDefinition\Listing();
            $list = $list->load();

            foreach ($list as $class) {
                $class->save();
            }
        }

        /**
         * @param Schema $schema
         */
        public function down(Schema $schema)
        {
            $this->writeMessage('Please execute bin/console pimcore:deployment:classes-rebuild afterwards.');
        }
    }
}

namespace Pimcore\Model\DataObject {
    interface CacheRawRelationDataInterface
    {
    }
}

namespace Pimcore\Model\DataObject\Traits {
    trait CacheRawRelationDataTrait
    {
    }
}
