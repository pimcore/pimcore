<?php

$token = getenv('POEDITOR_TOKEN');

$projects = [
    38068 => [
        'zh-CN',
        'cs',
        'nl',
        'fr',
        'de',
        'hu',
        'it',
        'ja',
        'fa',
        'pl',
        'pt-br',
        'ru',
        'sk',
        'es',
        'sv',
        'sv-fi',
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
            ($projectId === 197253) ? 'extended.' : ''
        );

        echo $file . "\n-------------------------------------\n";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"https://api.poeditor.com/v2/projects/export");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,
            sprintf("api_token=%s&id=%s&language=%s&type=key_value_json", $token, $projectId, $language));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $responseJson = @json_decode($response, true);
        if($responseJson && isset($responseJson['result']) && isset($responseJson['result']['url'])) {
            $contents = file_get_contents($responseJson['result']['url']);
            echo $contents;
            echo "\n-----------------------------------------------------------\n\n\n";
            //file_put_contents($file, $contents);
        } else {
            var_dump($response);
            var_dump($responseJson);
            exit(1);
        }

        echo "\n\n\n";
    }
}
