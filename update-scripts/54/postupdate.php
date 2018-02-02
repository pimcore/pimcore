<?php

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

$configFile = implode(DIRECTORY_SEPARATOR, [
    PIMCORE_APP_ROOT,
    'config',
    'local',
    'update_54_legacy_naming.yml'
]);

$configData = [
    'pimcore' => [
        'documents' => [
            'editables' => [
                'naming_strategy' => 'legacy'
            ]
        ]
    ]
];

$fs = new Filesystem();
$relativeConfigFile = $fs->makePathRelative($configFile, PIMCORE_PROJECT_ROOT);

$message = <<<EOF
This build introduces a new naming scheme for hierarchical document editables which is not compatible with previous
editable names. To ensure your installation does not break with this update, the naming scheme will be configured to use
the "legacy" naming scheme in <code>{$relativeConfigFile}</code>. If you don't use any nested editables (areablocks, blocks)
in your current installation, you can safely remove the config file and start using the new naming scheme. If you use
nested editables, a migration script for the new naming scheme will be implemented in <a href="https://github.com/pimcore/pimcore/issues/1525">#1525</a>.
In the meantime, please make sure your configuration is set to use the legacy naming scheme, either by keeping the file
created or by moving the config entry to your main config file.<br>
Please note that copy/paste from/to areablocks will be unavailable for the legacy naming scheme.
EOF;

echo sprintf('<p><strong>IMPORTANT:</strong> %s</p>' . PHP_EOL, $message);

try {
    $yaml = Yaml::dump($configData, 100);
    $yaml = '# created by build 54 - see https://github.com/pimcore/pimcore/issues/1467' . "\n" . $yaml;

    $fs->dumpFile($configFile, $yaml);
} catch (Exception $e) {
    echo sprintf(PHP_EOL . '<p><strong style="color: red">ERROR:</strong> Failed to write YML config to <code>%s</code>: %s</p>' . PHP_EOL, $configFile, $e->getMessage());
    echo <<<EOF
<p>Please add the following configuration manually:<br>
<pre>
pimcore:
    documents:
        editables:
            naming_strategy: legacy
</pre>
</p>
EOF;
}
