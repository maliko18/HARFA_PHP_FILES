<?php
include './utils.php';
function createUser($db, $createUserData)
{
    $userFirstName = $createUserData["userFirstName"];
    $userLastName = $createUserData["userLastName"];
    $userEmail = $createUserData["userEmail"];
    $phoneNumber = $createUserData["phoneNumber"];
    $userPassword = $createUserData["userPassword"];


    if (!$phoneNumber || !$userEmail || !$userPassword) {
        return array(
            'status' => 'nok',
            'status_message' => 'missing params'
        );
    }

    //check if mail already exist
    $select = $db->prepare("SELECT `email` FROM `user` WHERE `email` = :email");
    $select->execute(array(':email' => $userEmail));
    $result = $select->fetchColumn();

    if ($result > 0) {
        return array(
            'status' => 'nok',
            'status_message' => 'user mail already exists'
        );
    }
    $cryptedPassword = password_hash($userPassword, PASSWORD_DEFAULT);

    $insert = $db->prepare('INSERT INTO `user` (`firstName`, `lastName`, `email`, `password`, `phoneNumber`) VALUES (?, ?, ?, ?, ?)');
    $insertResult = $insert->execute(["$userFirstName", "$userLastName", "$userEmail", "$cryptedPassword", "$phoneNumber"]);

    if (!$insertResult) {
        return array(
            'status' => 'nok',
            'status_message' => 'user creation failed, db error'
        );
    }

    if (!$createUserData["work"]) {
        return array(
            'status' => 'ok'
        );
    }
    //else add profession

    $select = $db->prepare("SELECT `userId` FROM `user` WHERE `email` = :email");
    $select->execute(array(':email' => $userEmail));
    $userId = $select->fetchColumn();

    if ($userId < 0) {
        return array(
            'status' => 'nok',
            'status_message' => 'user creation failed, db error'
        );
    }


    $work = $createUserData["work"];
    $experience = $createUserData["experience"];
    $description = $createUserData["description"];
    $pricePerHour = $createUserData["pricePerHour"];

    $insert = $db->prepare('INSERT INTO `profession` (`title`, `experience`, `description`, `pricePerHour`, `userId`) VALUES (?, ?, ?, ?, ?)');
    $insertResult = $insert->execute(["$work", "$experience", "$description", "$pricePerHour", "$userId"]);

    if ($insertResult) {
        return array(
            'status' => 'ok'
        );
    } else {
        return array(
            'status' => 'nok',
            'status_message' => 'user creation failed, db error'
        );
    }

}


function login($db, $data)
{
    $email = $data["userEmail"];
    $userPassword = $data["userPassword"];

    if (!$email || !$userPassword) {
        return array(
            'status' => 'nok',
            'status_message' => 'missing params'
        );
    }

    $select = $db->prepare("SELECT `password`,`userId` FROM `user` WHERE `email` = :email");
    $select->execute(array(':email' => $email));
    $row = $select->fetch();
    $psw = $row["password"];
    $userId = $row["userId"];
    if (!$psw || !password_verify($userPassword, $psw)) {
        return array(
            'status' => 'nok',
            'status_message' => "email / password doesn't exist"
        );
    }

    $sessKey = session_create_id("hirfa") . session_create_id("app");

    $insert = $db->prepare('INSERT INTO `sessions` (`sessionKey`, `userId`) VALUES (?, ?)');
    $insertResult = $insert->execute(["$sessKey", "$userId"]);

    if ($insertResult) {
        return array(
            'status' => 'ok',
            'sessKey' => $sessKey
        );
    } else {
        return array(
            'status' => 'nok',
            'status_message' => "email / password doesn't exist"
        );
    }
}


function createDemande($db, $data)
{
    $userId = getUserIdFromSessionKey($db, $data["sessKey"]);

    if (!$userId) {
        return array(
            'status' => 'nok',
            'status_message' => "invalide session key"
        );
    }
    $message = $data["message"];
    $date = $data["date"];
    $professionId = $data["professionId"];

    if (!$message || !$date || !$professionId) {
        return array(
            'status' => 'nok',
            'status_message' => "missing params"
        );
    }


    $insert = $db->prepare('INSERT INTO `demande` (`userId`, `professionId`, `message`, `status`, `date`) VALUES (?, ?, ?, ?, ?)');
    $insertResult = $insert->execute(["$userId", "$professionId", "$message", 0, $date]);
    if (!$insertResult) {
        return array(
            'status' => 'nok',
            'status_message' => "db error",
        );
    }

    return array(
        'status' => 'ok',
    );
}

function changeDemandeStatus($db, $data)
{
    $userId = getUserIdFromSessionKey($db, $data["sessKey"]);

    if (!$userId) {
        return array(
            'status' => 'nok',
            'status_message' => "invalide session key"
        );
    }
    /*
     -2  = deleted
     -1  = refused
     0   = waiting
     1   = accepted
     2   = canceled
    */
    $demandeStatus = $data["demandeStatus"];
    $demandeId = $data["demandeId"];

    $select = $db->prepare("SELECT profession.userId as workerUserId, demande.userId as clientUserId FROM `profession` INNER JOIN `demande` WHERE demande.id = :demandeId and demande.professionId = profession.id");
    $select->execute(array(':demandeId' => $demandeId));
    $row = $select->fetch();

    if (!$row) {
        return array(
            'status' => 'nok',
            'status_message' => "db error"
        );
    }

    $workerId = $row["workerUserId"];
    $clientUserId = $row["clientUserId"];
    if ($workerId == $userId && ($demandeStatus == 1 || $demandeStatus == -1)) {
        // he can accept / refuse only 
        $update = $db->prepare("UPDATE `demande` SET `status` = :newStatus WHERE `id` = :demandeId");
        $result = $update->execute(array(':demandeId' => $demandeId, ':newStatus' => $demandeStatus));
        if (!$result) {
            return array(
                'status' => 'nok',
                'status_message' => "db error"
            );
        }

    } else if ($clientUserId == $userId && ($demandeStatus == -2 || $demandeStatus == 2)) {
        // he can delete, cancel demande
        if ($demandeStatus == 2) {
            $update = $db->prepare("UPDATE `demande` SET `status` = :newStatus WHERE `id` = :demandeId");
            $result = $update->execute(array(':demandeId' => $demandeId, ':newStatus' => $demandeStatus));
            if (!$result) {
                return array(
                    'status' => 'nok',
                    'status_message' => "db error"
                );
            }
        } else {
            $delete = $db->prepare("DELETE FROM `demande` WHERE `id` = :demandeId");
            $result = $delete->execute(array(':demandeId' => $demandeId));
            if (!$result) {
                return array(
                    'status' => 'nok',
                    'status_message' => "db error"
                );
            }
        }

    } else {
        return array(
            'status' => 'nok',
            'status_message' => "access denied"
        );
    }
    return array("status" => "ok");
}

function addReview($db, $data)
{
    $userId = getUserIdFromSessionKey($db, $data["sessKey"]);

    if (!$userId) {
        return array(
            'status' => 'nok',
            'status_message' => "invalide session key"
        );
    }

    $professionId = $data["professionId"];
    $message = $data["message"];
    $note = $data["note"];

    if (!$professionId || !$message || !$note) {
        return array(
            'status' => 'nok',
            'status_message' => 'missing params'
        );
    }

    $select = $db->prepare("SELECT `idUser` FROM `review` WHERE `idUser` = :idUser and idProfession = :idProfession");
    $select->execute(array(':idUser' => $userId, ':idProfession' => $professionId));
    $result = $select->fetchColumn();
    if ($result > 0) {
        return array(
            'status' => 'nok',
            'status_message' => 'comment already added'
        );
    }

    if ($note > 5 || $note < 1) {
        return array(
            'status' => 'nok',
            'status_message' => 'note must be between 1 and 5'
        );
    }

    $insert = $db->prepare('INSERT INTO `review` (`idUser`, `idProfession`, `note`, `comment`) VALUES (?, ?, ?, ?)');
    $insertResult = $insert->execute(["$userId", "$professionId", "$note", "$message"]);

    if (!$insertResult) {
        return array(
            'status' => 'nok',
            'status_message' => 'db error'
        );
    }

    $selectComment = $db->prepare("SELECT `firstName`,`lastName`,`note`,`idUser` as userId, `comment` 
                            FROM `review` INNER JOIN user ON review.idUser = user.userId
                            where idProfession = :idProfession and idUser = :userId");
    $selectComment->execute(array(':idProfession' => $professionId, ':userId' => $userId));
    $newComment = $selectComment->fetch();
    return array(
        'newComment' => $newComment,
        'status' => 'ok'
    );
}
?>