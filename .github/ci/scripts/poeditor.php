<?php

$token = getenv('POEDITOR_TOKEN');

$projects = [
    38068 => [
        'zh_Hans',
        'cs',
        'nl',
        'fr',
        'de',
        'hu',
        'it',
        'ja',
        'fa',
        'pl',
        'pt_BR',
        'ru',
        'sk',
        'es',
        'sv',
        'sv_FI',
        'th',
        'tr',
        'uk',
    ],
    197253 => [
        'cs',
        'nl',
        'de',
        'hu',
        'it',
        'pl',
        'sk',
        'es',
        'th',
    ]
];

foreach($projects as $projectId => $languages) {
    foreach($languages as $language) {
        $file = sprintf('bundles/CoreBundle/Resources/translations/%s.%sjson',
            $language,
            ($projectId === 197253) ? '.extended' : ''
        );

        echo $file . "\n";
        @exec('curl -X POST https://api.poeditor.com/v2/projects/export -d api_token="' . $token . '" -d id="' . $projectId . '" -d language="de" -d type="key_value_json" -o ' . $file, $output, $returnCode);
        if($returnCode !== 0) {
            echo sprintf('Unable to retrieve translations for project %s with language %s', $projectId, $language);
            exit($returnCode);
        }
    }
}
