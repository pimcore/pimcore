<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Config;
use Pimcore\File;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180913132106 extends AbstractPimcoreMigration
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
        $showMailWarning = false;

        // change swiftmailer transport method from "mail"(deprecated) to "sendmail" for email & newsletter configuration
        $existingConfig = Config::getSystemConfig();
        $settings = $existingConfig->toArray();

        $configs = ['email', 'newsletter'];

        foreach ($configs as $key => $config) {
            if (isset($settings[$config]['method']) && $settings[$config]['method'] == 'mail') {
                $showMailWarning = true;
                $settings[$config]['method'] = 'sendmail';
            }
        }

        $configFile = \Pimcore\Config::locateConfigFile('system.php');
        File::putPhpFile($configFile, to_php_data_file_format($settings));

        //show warning if mail method is configured for email or newsletter
        if ($showMailWarning) {
            $this->writeMessage('The email method mail() is not supported anymore, please configure sendmail or SMTP in your system settings in order to use Pimcores email capabilities. <error>WARNING: Emails are not going to work until you have changed your configuration!</error>');
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
