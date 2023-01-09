<?php
declare(strict_types=1);

/**
 * This script exports translations which are maintained in English to POEditor
 * where all the other languages are managed
 */

$apiToken = getenv('POEDITOR_TOKEN');

// POEditor project IDs
$projectMapping = [
    'essentials' => 38068,
    //'extended' => 197253,
    'extended' => 585539,
];

$projectConfig = array_filter(explode("\n", trim(getenv('TRANSLATION_FILES'))));
$translationFiles = [];

foreach ($projectConfig as $line) {
    list($sourcePath, $projectKey) = array_map('trim', explode(':', $line));
    if(isset($projectMapping[$projectKey])) {
        $translationFiles[$sourcePath] = $projectMapping[$projectKey];
    }
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


foreach($translationFiles as $sourceUrl => $projectId) {

    $data = [];
    $dataEn = [];
    $enData = json_decode(file_get_contents($sourceUrl), true);

    $reference = getenv('GITHUB_REPOSITORY') . ':' . $sourceUrl;

    foreach ($enData as $key => $value) {
        $data[] = [
            "term" => $key,
            "context" => '',
            "reference" => $reference,
        ];

        $dataEn[] = [
            "term" => [
                "term" => $key,
                "context" => ""
            ],
            "definition" => [
                "forms" => [
                    $value
                ]
            ]
        ];
    }

    $dataString = json_encode($data);
    $dataEnString = json_encode($dataEn);

    if (count($data)) {
        echo sprintf('Running add_terms for %s with project ID %s', $sourceUrl, $projectId) . "\n";
        try {
            $response = $getPostValues("https://poeditor.com/api/", [
                "api_token" => $apiToken,
                "action" => "add_terms",
                "id" => $projectId,
                "data" => $dataString
            ]);

            print_r($response);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        echo "\n\n";
        echo sprintf('Running update_language for %s with project ID %s', $sourceUrl, $projectId) . "\n";
        try {
            $response = $getPostValues("https://poeditor.com/api/", [
                "api_token" => $apiToken,
                "action" => "update_language",
                "id" => $projectId,
                "language" => "en",
                "data" => $dataEnString
            ]);

            print_r($response);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        echo "\n\n";
        echo "###############################################";
        echo "\n\n";
    }
}

