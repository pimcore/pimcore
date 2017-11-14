<?php

$message = <<<EOF
The Piwik integration now requires to configure a full URI to the Piwik installation including protocol. If you are using
the Piwik integration please update your settings accordingly as Piwik tracking will be disabled until you update the URL.
Plase have a look at the <a target="_blank" href="https://pimcore.com/docs/5.0.x/Installation_and_Upgrade/Upgrade_Notes/Within_V5.html#page_Build_149_2017_11_14">upgrade docs</a>
for details.
EOF;

echo sprintf('<p>%s</p>' . PHP_EOL, $message);
