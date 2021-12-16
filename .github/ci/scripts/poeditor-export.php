<?php

/**
 * This script exports translations which are maintained in English to POEditor
 * where all the other languages are managed
 */

$apiToken = getenv('POEDITOR_TOKEN');

$projects = [
    38068 => "bundles/CoreBundle/Resources/translations/en.json",
    197253 => "bundles/CoreBundle/Resources/translations/en.extended.json"
];

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


foreach($projects as $projectId => $sourceUrl) {

    $data = [];
    $dataEn = [];
    $enData = json_decode(file_get_contents($sourceUrl), true);

    foreach ($enData as $key => $value) {
        $data[] = [
            "term" => $key,
            "context" => "",
            "reference" => "",
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

    if (count($data) > 500) {
        try {
            $response = $getPostValues("https://poeditor.com/api/", [
                "api_token" => $apiToken,
                "action" => "sync_terms",
                "id" => $projectId,
                "data" => $dataString
            ]);

            print_r($response);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        echo "\n\n";

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
    }
}

