<?php

$files = ["extensions","customviews","reports","system"];

foreach($files as $fileName) {
    $xmlFile = \Pimcore\Config::locateConfigFile($fileName . ".xml");

    if (file_exists($xmlFile)) {
        $phpFile = \Pimcore\Config::locateConfigFile($fileName . ".php");

        try {
            $config = new \Zend_Config_Xml($xmlFile);
            $contents = $config->toArray();

            if(!is_writable(dirname($phpFile))) {
                throw new \Exception($phpFile . " is not writable");
            }

            if ($fileName == "customviews") {
                $cvData = [];
                if (isset($contents["views"]["view"][0])) {
                    $cvData = $contents["views"]["view"];
                } else {
                    $cvData[] = $contents["views"]["view"];
                }

                $contents = [
                    "views" => $cvData
                ];
            }

            $contents = var_export_pretty($contents);
            $phpContents = "<?php \n\nreturn " . $contents . ";\n";

            \Pimcore\File::put($phpFile, $phpContents);
        } catch (\Exception $e) {
            \Logger::crit($e);

            echo "<b>Critical ERROR!</b><br />";
            echo $e->getMessage();
            echo "<br />Please try to fix it an run the update again.";
            exit;
        }
    }
}
