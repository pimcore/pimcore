<?php

### CONFIGURATION ###

// setup include pathes
$includePaths = array(
    "/home/pimcore/www/pimcore/lib",
    get_include_path()
);
set_include_path(implode(PATH_SEPARATOR, $includePaths));


### START ###

// setup zend framework
require_once "Zend/Loader.php";
require_once "Zend/Loader/Autoloader.php";

$autoloader = Zend_Loader_Autoloader::getInstance();




// ### EXECUTE TESTS ###
$outputDir = "/home/pimcore/www/tests/output";
$outputFile = "output";
if (!is_dir($outputDir) or !is_writable($outputDir) or (is_writable($outputDir) and is_file($outputDir . "/" . $outputFile) and !is_writable($outputDir . "/" . $outputFile))) {
    echo "Aborting, cannot write unit test output file.\n";
    die();
}
if(is_file($outputDir . "/" . $outputFile)){
   unlink($outputDir . "/" . $outputFile); 
}

exec("phpunit --bootstrap /home/pimcore/www/tests/bootstrap.php --log-xml " . $outputDir . "/" . $outputFile . " AllTests");
exec("chmod 777 ".$outputDir . "/" . $outputFile);
$xmlString = file_get_contents($outputDir . "/" . $outputFile);
if (empty($xmlString)) {
    echo "unit tests failed aborting build";
    die();
}
$xml = simplexml_load_string($xmlString);
$mainsuite = $xml->xpath('/testsuites/testsuite[1]');
$mainAttributes = $mainsuite[0]->attributes();

$failures=  $mainAttributes["failures"];
$errors = $mainAttributes["errors"];

echo "Executed [ " . $mainAttributes["tests"] . " ] unittests with [ " . $mainAttributes["assertions"] . " ] assertions\n";

if ($failures > 0 or $errors > 0) {
    echo "There were [ $errors ] errors and [ $failures ] failures in the unit tests, aborting.\n";
    die();
}
