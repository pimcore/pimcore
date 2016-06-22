<?php

$file = \Pimcore\Config::locateConfigFile("classmap.php");
if(file_exists($file)) {
    $data = include $file;

    $diContents = "<?php\n\nreturn [\n";

    foreach($data as $source => $target) {
        $diContents .= '    \'Pimcore\Model\\' . $source . '\' => DI\object(\'' . $target . '\'),'."\n";
    }

    $diContents .= "];\n";

    $customFile = \Pimcore\Config::locateConfigFile("di.php");
    \Pimcore\File::put($customFile, $diContents);
}

