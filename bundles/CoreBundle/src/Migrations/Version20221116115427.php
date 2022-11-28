<?php

declare(strict_types=1);

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\User;
use Pimcore\Model\User\Listing;


final class Version20221116115427 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add "object bricks" permission';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO `users_permission_definitions` (`key`, `category`) VALUES ('objectbricks', '')");

        $users = new Listing();
        /**
         * @var User $user
         */
        foreach ($users as $user) {
            if ($user->isAllowed('classes')) {
                $permissions = $user->getPermissions();
                $permissions[] = 'objectbricks';
                $user->setPermissions($permissions);
                $user->save();
            }
        }
    }

    public function down(Schema $schema): void
    {
        $users = new Listing();
        /**
         * @var User $user
         */
        foreach ($users as $user) {
            $permissions = $user->getPermissions();
            if ($permissions) {
                $permissions = \array_filter($permissions, static function ($element) {
                    return $element !== "objectbricks";
                });

                $user->setPermissions($permissions);
                $user->save();
            }
        }

        $this->addSql("DELETE FROM `users_permission_definitions` WHERE `key` = 'objectbricks'");
    }
}
