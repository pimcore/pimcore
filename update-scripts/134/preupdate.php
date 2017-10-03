<?php

use Pimcore\Bundle\EcommerceFrameworkBundle\PimcoreEcommerceFrameworkBundle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

// only add mapping if the ecommerce framework bundle is enabled
if (!PimcoreEcommerceFrameworkBundle::isEnabled()) {
    return;
}

$configFile = PIMCORE_APP_ROOT . '/config/local/update_134_ecommerce_legacy_mapping.yml';

$configData = [
    'pimcore_ecommerce_framework' => [
        'use_legacy_class_mapping' => true
    ]
];

$fs = new Filesystem();
$relativeConfigFile = $fs->makePathRelative($configFile, PIMCORE_PROJECT_ROOT);

try {
    $yaml = Yaml::dump($configData, 100);
    $yaml = '# created by build 134 - you can safely remove this if you don\'t need any legacy class mapping' . "\n" . $yaml;

    $fs->dumpFile($configFile, $yaml);
} catch (Exception $e) {
    echo sprintf(PHP_EOL . '<p><strong style="color: red">ERROR:</strong> Failed to write YML config to <code>%s</code>: %s</p>' . PHP_EOL, $configFile, $e->getMessage());
    echo <<<EOF
<p>Please add the following configuration manually:<br>
<pre>
pimcore_ecommerce_framework:
    use_legacy_class_mapping: true
</pre>
</p>
EOF;
}
