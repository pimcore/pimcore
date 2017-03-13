<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Console\Command;

use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pimcore\Model\Asset;
use Pimcore\File;

class InternalUnicodeCldrLanguageTerritoryGeneratorCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('internal:unicode-cldr-language-territory-generator')
            ->setDescription('For internal use only');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $source = "http://unicode.org/repos/cldr/trunk/common/supplemental/supplementalData.xml";
        $data = file_get_contents($source);
        $xml = simplexml_load_string($data, null, LIBXML_NOCDATA);

        $languageRawData = [];

        foreach ($xml->territoryInfo->territory as $territory) {
            foreach ($territory->languagePopulation as $language) {
                $languageCode = (string) $language["type"];
                if (\Pimcore::getContainer()->get("pimcore.locale")->isLocale($languageCode)) {
                    $populationAbsolute = $territory["population"] * $language["populationPercent"] / 100;

                    if (!isset($languageRawData[$languageCode])) {
                        $languageRawData[$languageCode] = [];
                    }

                    if (\Pimcore::getContainer()->get("pimcore.locale")->isLocale($languageCode . "_" . $territory["type"])) {
                        $languageRawData[$languageCode][] = [
                            "country" => (string)$territory["type"],
                            "population" => $populationAbsolute
                        ];
                    }
                }
            }
        }

        $finalData = [];

        foreach ($languageRawData as $languageCode => $rawLanguage) {
            usort($rawLanguage, function ($a, $b) {
                if ($a["population"] == $b["population"]) {
                    return 0;
                }

                return ($a["population"] > $b["population"]) ? -1 : 1;
            });

            $finalData[$languageCode] = [];
            foreach ($rawLanguage as $territory) {
                $finalData[$languageCode][] = $territory["country"];
            }
        }

        $contents = to_php_data_file_format($finalData);
        $dataFile = PIMCORE_PATH . "/lib/Pimcore/Bundle/PimcoreBundle/Resources/misc/cldr-language-territory-mapping.php";
        File::putPhpFile($dataFile, $contents);

        $this->output->writeln("Updated mappings in " . $dataFile);
    }
}
