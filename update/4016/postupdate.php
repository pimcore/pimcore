<?php

$config = \Pimcore\WorkflowManagement\Workflow\Config::getWorkflowManagementConfig(true);
$config = $config['workflows'];

$workflows = [];

foreach($config as $workflow) {
    $workflow['creationDate'] = \Carbon\Carbon::now()->getTimestamp();
    $workflow['modificationDate'] = \Carbon\Carbon::now()->getTimestamp();

    $workflows[$workflow['id']] = $workflow;
}

$contents = to_php_data_file_format($workflows);
\Pimcore\File::putPhpFile(\Pimcore\Config::locateConfigFile('workflowmanagement.php'), $contents);
