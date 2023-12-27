<?php
    function logout($db, $data){
        $sessionKey = $data["sessKey"];
        $delete = $db->prepare("DELETE FROM `sessions` WHERE `sessionKey` = :sessionValue");
        $delete->execute(array(':sessionValue' => $sessionKey));
        return array(
            'status' => 'ok'
        );
    }
?>