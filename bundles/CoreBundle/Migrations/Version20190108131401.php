<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;
use Pimcore\Model\Tool\CustomReport\Config;
use Pimcore\Model\User;

class Version20190108131401 extends AbstractPimcoreMigration
{
    public function doesSqlMigrations(): bool
    {
        return true;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->writeMessage('Adding new Permission...');

        $this->addSql("INSERT IGNORE INTO users_permission_definitions (`key`) VALUES('reports_config');");

        $this->writeMessage('Updating users and adding new permission "reports_config" if necessary ...');

        $users = new User\Listing();
        $users = $users->load();

        foreach ($users as $user) {
            if ($user instanceof User && $user->isAllowed('reports')) {
                $this->writeMessage('Updating user ' . $user->getName());
                $user->setPermission('reports_config', true);
                $user->save();
            }
        }

        $this->writeMessage('Updating roles and adding new permission "reports_config" if necessary ...');

        $roles = new User\Role\Listing();
        $roles = $roles->load();
        foreach ($roles as $role) {
            if ($role instanceof User\Role && $role->getPermission('reports')) {
                $this->writeMessage('Updating user ' . $role->getName());
                $role->setPermission('reports_config', true);
                $role->save();
            }
        }

        $this->writeMessage('Updating custom reports and set all to shared globally initially ...');

        $reports = new Config\Listing();
        $reports = $reports->getDao()->load();
        foreach ($reports as $report) {
            if ($report->getShareGlobally() === null) {
                $report->setShareGlobally(true);
                $report->save();
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->writeMessage('Removing new Permission...');

        $this->addSql("DELETE FROM users_permission_definitions WHERE `key` = 'reports_config';");

        $this->writeMessage('Updating users and removing new permission "reports_config" if necessary ...');

        $users = new User\Listing();
        $users = $users->load();

        foreach ($users as $user) {
            if ($user instanceof User && $user->isAllowed('reports_config')) {
                $this->writeMessage('Updating user ' . $user->getName());
                $user->setPermission('reports_config', false);
                $user->save();
            }
        }

        $this->writeMessage('Updating roles and removing new permission "reports_config" if necessary ...');

        $roles = new User\Role\Listing();
        $roles = $roles->load();
        foreach ($roles as $role) {
            if ($role instanceof User\Role && $role->getPermission('reports_config')) {
                $this->writeMessage('Updating user ' . $role->getName());
                $role->setPermission('reports_config', false);
                $role->save();
            }
        }
    }
}
