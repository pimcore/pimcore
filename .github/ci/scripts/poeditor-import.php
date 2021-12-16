<?php

/**
 * This script imports all translations from all languages from POEditor which have a
 * percentage over 70%
 */

$token = getenv('POEDITOR_TOKEN');
$languagesString = getenv('POEDITOR_LANGUAGES');
$projects = [38068, 197253];

$allowedLanguages = [];
if(!empty($languagesString)) {
    $allowedLanguages = array_map('trim', explode(',', $languagesString));
}

$getPostValues = function ($url, array $params) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    return @json_decode($response, true);
};

$getFile = function ($projectId, $language) {
    return sprintf('bundles/CoreBundle/Resources/translations/%s.%sjson',
        $language,
        ($projectId === 197253) ? 'extended.' : ''
    );
};

echo "Allowed languages: \n\n";
var_dump($allowedLanguages);
echo "\n\n";

foreach($projects as $projectId) {

    $responseJson = $getPostValues('https://api.poeditor.com/v2/languages/list', [
        'api_token' => $token,
        'id' => $projectId,
    ]);

    $languages = [];
    if($responseJson && isset($responseJson['result']) && isset($responseJson['result']['languages'])) {
        foreach($responseJson['result']['languages'] as $language) {
            if($language['percentage'] > 70) {
                // add language to be updated or added
                $languages[] = $language['code'];
            } else {
                // language isn't over 70%, skip it and delete existing
                $file = $getFile($projectId, $language['code']);
                if(file_exists($file)) {
                    echo sprintf("Deleted %s because it's not over 70 percent translated.\n", $file);
                    unlink($file);
                }
            }
        }
    } else {
        echo sprintf("Retrieving languages for project %s failed\n", $projectId);
        var_dump($responseJson);
    }

    foreach($languages as $language) {

        if($language === 'en') {
            continue;
        }

        if(!empty($allowedLanguages) && !in_array($language, $allowedLanguages)) {
            echo sprintf('Skipped language %s', $language) . "\n";
            continue;
        }

        $file = $getFile($projectId, $language);
        echo $file . "\n-------------------------------------\n";

        $responseJson = $getPostValues('https://api.poeditor.com/v2/projects/export', [
            'api_token' => $token,
            'id' => $projectId,
            'language' => $language,
            'type' => 'key_value_json',
        ]);

        if($responseJson && isset($responseJson['result']) && isset($responseJson['result']['url'])) {
            $contents = file_get_contents($responseJson['result']['url']);
            //echo $contents;
            //echo "\n-----------------------------------------------------------\n\n\n";
            file_put_contents($file, $contents);
        } else {
            var_dump($responseJson);
            exit(1);
        }

        echo "\n\n\n";
    }
}
