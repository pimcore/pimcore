<?php

$folders = [PIMCORE_CLASS_DIRECTORY . "/objectbricks",PIMCORE_CLASS_DIRECTORY . "/fieldcollections"];
foreach($folders as $folder) {
    $files = glob($folder . "/*.psf");

    foreach ($files as $file) {
        $data = file_get_contents($file);
        $definition = \Pimcore\Tool\Serialize::unserialize($data);
        $phpDefinitionFile = preg_replace("@\.psf$@", ".php", $file);

        $clone = clone $definition;
        $clone->setDao(null);
        unset($clone->fieldDefinitions);

        $exportedClass = var_export($clone, true);

        $data = '<?php ';
        $data .= "\n\n";

        $data .= "\nreturn " . $exportedClass . ";\n";

        \Pimcore\File::put($phpDefinitionFile, $data);
    }
}
