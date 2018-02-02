<?php

$message = <<<EOF
The navigation view helper signature changed with build 60 and now handles building and rendering the navigation
in 2 distinct steps. If you use the navigation view helper, please update your views accordingly. Plase have a look
at the <a href="https://www.pimcore.org/docs/5.0.0/Documents/Navigation.html">navigation docs</a> for details.
EOF;

echo sprintf('<p> %s</p>' . PHP_EOL, $message);
