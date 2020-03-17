<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\File;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;
use Pimcore\Model\DataObject;
use Pimcore\Tool\Serialize;

class Version20181115114003 extends AbstractPimcoreMigration
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
        $this->addSql('ALTER TABLE `custom_layouts` ALTER `id` DROP DEFAULT; ALTER TABLE `custom_layouts` CHANGE COLUMN `id` `id` VARCHAR(64) NOT NULL FIRST;');

        $customLayouts = new DataObject\ClassDefinition\CustomLayout\Listing();
        $customLayouts = $customLayouts->load();
        $cleanupRequired = false;

        foreach ($customLayouts as $customLayout) {
            $psfFile = PIMCORE_CUSTOMLAYOUT_DIRECTORY . '/custom_definition_'. $customLayout->getId() .'.psf';

            if (is_file($psfFile)) {
                $layoutDefinition = Serialize::unserialize(file_get_contents($psfFile));
                unset($customLayout->fieldDefinitions);
                $customLayout->setLayoutDefinitions($layoutDefinition);

                $phpFile = $this->updateCustomLayoutDefinitionToPhp($customLayout);

                if (is_file($phpFile)) {
                    $cleanupRequired = true;
                }
            }
        }

        if ($cleanupRequired) {
            $this->writeMessage('Please cleanup custom_definition_*.psf files from directory '.PIMCORE_CUSTOMLAYOUT_DIRECTORY.' manually as they are no longer used by Custom Layouts.');
        }
    }

    /**
     * @param DataObject\ClassDefinition\CustomLayout $customLayout
     *
     * @return bool|string
     */
    public function updateCustomLayoutDefinitionToPhp($customLayout)
    {
        // save definition as a php file
        $definitionFile = $this->getDefinitionFile($customLayout->getId());
        if (!is_writable(dirname($definitionFile)) || (is_file($definitionFile) && !is_writable($definitionFile))) {
            throw new \Exception(
                'Cannot write definition file in: '.$definitionFile.' please check write permission on this directory.'
            );
        }

        try {
            $infoDocBlock = $this->getInfoDocBlock();
            $clone = clone $customLayout;
            $clone->setDao(null);
            unset($clone->fieldDefinitions);

            self::cleanupForExport($clone->layoutDefinitions);

            $exportedCustomLayout = var_export($clone, true);

            $data = '<?php ';
            $data .= "\n\n";
            $data .= $infoDocBlock;
            $data .= "\n\n";

            $data .= "\nreturn ".$exportedCustomLayout.";\n";

            \Pimcore\File::putPhpFile($definitionFile, $data);

            return $definitionFile;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param string $layoutId
     *
     * @return string
     */
    public function getDefinitionFile($layoutId)
    {
        $file = PIMCORE_CUSTOMLAYOUT_DIRECTORY.'/custom_definition_'. $layoutId .'.php';

        return $file;
    }

    /**
     * @return string
     */
    public function getInfoDocBlock()
    {
        $cd = '';

        $cd .= '/** ';
        $cd .= "\n";
        $cd .= '* Generated at: '.date('c')."\n";

        $cd .= '* Changed by: system (0)'."\n";

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $cd .= '* IP: '.$_SERVER['REMOTE_ADDR']."\n";
        }

        $cd .= '*/ ';

        return $cd;
    }

    /**
     * @param DataObject\ClassDefinition\Layout|DataObject\ClassDefinition\Data $data
     */
    public static function cleanupForExport(&$data)
    {
        if (isset($data->fieldDefinitionsCache)) {
            unset($data->fieldDefinitionsCache);
        }

        if (method_exists($data, 'getChilds')) {
            $children = $data->getChilds();
            if (is_array($children)) {
                foreach ($children as $child) {
                    self::cleanupForExport($child);
                }
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE `custom_layouts` CHANGE COLUMN `id` `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT;');
    }
}
