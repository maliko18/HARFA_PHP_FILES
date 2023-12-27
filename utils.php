<?php

function getUserIdFromSessionKey($db, $sessionKey)
{
    if (!$sessionKey) {
        return false;
    }
    $select = $db->prepare("SELECT `userId` FROM `sessions` WHERE `sessionKey` = :sessKey");
    $select->execute(array(':sessKey' => $sessionKey));
    return $select->fetchColumn();
}

?>