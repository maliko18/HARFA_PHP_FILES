<?php

function getProfile($db, $data)
{
    $sessionKey = $data["sessKey"];

    $select = $db->prepare("SELECT `userId` FROM `sessions` WHERE `sessionKey` = :sessKey");
    $select->execute(array(':sessKey' => $sessionKey));
    $userId = $select->fetchColumn();
    if ($userId < 0) {
        return array(
            'status' => 'nok',
            'status_message' => 'invalide session key'
        );
    }

    $select = $db->prepare("SELECT `userId`,`firstName`,`lastName`,`phoneNumber`,`email`  FROM `user` WHERE `userId` = :userId");
    $select->execute(array(':userId' => $userId));
    $user = $select->fetch();
    if (!$user) {
        return array(
            'status' => 'nok',
            'status_message' => 'invalide session key'
        );
    }
    $result = array(
        'userId' => $user['userId'],
        'firstName' => $user['firstName'],
        'lastName' => $user['lastName'],
        'phoneNumber' => $user['phoneNumber'],
        'email' => $user['email'],
    );


    $select = $db->prepare("SELECT `id`,`title`,`experience`,`description`,`pricePerHour`  FROM `profession` WHERE `userId` = :userId");
    $select->execute(array(':userId' => $userId));
    $profession = $select->fetch();

    if (!$profession) {
        return array(
            'user' => $result,
            'status' => 'ok'
        );
    }

    $result['profession'] = array(
        'id' => $profession['id'],
        'title' => $profession['title'],
        'experience' => $profession['experience'],
        'description' => $profession['description'],
        'pricePerHour' => $profession['pricePerHour'],
    );

    return array(
        'user' => $result,
        'status' => 'ok'
    );
}

function getWorkerList($db)
{
    $select = $db->prepare("SELECT `id`,`title`,`experience`,`description`,`pricePerHour`,profession.userId,`firstName`,`lastName` 
                            FROM `profession` INNER JOIN user ON profession.userId = user.userId");
    $select->execute();
    $result = array();
    while($row = $select->fetch(PDO::FETCH_ASSOC)){
        $idProfession = $row["id"];

        $selectComment = $db->prepare("SELECT `firstName`,`lastName`,`note`,`idUser` as userId, `comment` 
                            FROM `review` INNER JOIN user ON review.idUser = user.userId
                            where idProfession = :idProfession");
        $selectComment->execute(array(':idProfession' => $idProfession));
        $row["comments"] = $selectComment->fetchAll(PDO::FETCH_ASSOC);
        $result[] = $row;
    }
    return array(
        "status" => "ok",
        'workers' => $result
    );
}

function getDemandesForUser($db, $data)
{
    $userId = getUserIdFromSessionKey($db, $data["sessKey"]);
    if (!$userId) {
        return array(
            'status' => 'nok',
            'status_message' => "access denied"
        );
    }

    $select = $db->prepare("SELECT demande.id, user.userId as workerId,`firstName`,`lastName`,`phoneNumber`,`email`, `title`, `pricePerHour`, `message`, `status`, `date` 
                                FROM `user` 
                                INNER JOIN `profession` ON user.userId = profession.userId
                                INNER JOIN `demande` ON profession.id = demande.professionId
                                WHERE demande.userId = :userId");

    $select->execute(array(':userId' => $userId));
    $result = $select->fetchAll(PDO::FETCH_ASSOC);
    return array(
        'demandes' => $result,
        'status' => 'ok'
    );
}

function getDemandesForWorker($db, $data)
{
    $userId = getUserIdFromSessionKey($db, $data["sessKey"]);
    
    if (!$userId) {
        return array(
            'status' => 'nok',
            'status_message' => "access denied"
        );
    }

    $getOnlyAcceptedRequests = $data["onlyAccepted"];
    if($getOnlyAcceptedRequests == "true"){
        $select = $db->prepare("SELECT demande.id, user.userId as clientId,`firstName`,`lastName`,`phoneNumber`,`email`, `message`, `status`,`date`
        FROM `user` 
        INNER JOIN `demande` ON user.userId = demande.userId
        INNER JOIN `profession` ON profession.id = demande.professionId
        WHERE profession.userId = :userId and demande.status = 1");
    }else{
        $select = $db->prepare("SELECT demande.id, user.userId as clientId,`firstName`,`lastName`,`phoneNumber`,`email`, `message`, `status`,`date`
        FROM `user` 
        INNER JOIN `demande` ON user.userId = demande.userId
        INNER JOIN `profession` ON profession.id = demande.professionId
        WHERE profession.userId = :userId");
    }
   
    $select->execute(array(':userId' => $userId));
    $result = $select->fetchAll(PDO::FETCH_ASSOC);
    return array(
        'demandes' => $result,
        'status' => 'ok'
    );
}

?>