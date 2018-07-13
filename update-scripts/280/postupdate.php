<?php
//setting replyTo = null if default empty value exists
$emailLogs = new \Pimcore\Model\Tool\Email\Log\Listing();
$emailLogs->setCondition("replyTo = ''");
foreach ($emailLogs->load() as $ekey => $emailLog) {
    $emailLog->setReplyTo(null);
    $emailLog->save();
}
