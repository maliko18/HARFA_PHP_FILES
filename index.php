<?php
include './db.php';
include './insert.php';
include './delete.php';
include './read.php';

$db = getDb();

$params = array_merge($_GET, $_POST);

$requestName = $params["req"];

try {
    $result = "";
    switch ($requestName) {
        case 'createUser':
            $result = createUser($db, $params);
            break;
        case 'login':
            $result = login($db, $params);
            break;
        case 'logout':
            $result = logout($db, $params);
        case 'getProfile':
            $result = getProfile($db, $params);
            break;
        case 'getWorkerList':
            $result = getWorkerList($db);
            break;
        case 'createDemandeForWorker':
            $result = createDemande($db, $params);
            break;
        case 'updateDemandeStatus':
            $result = changeDemandeStatus($db, $params);
            break; //getDemandesForWorker
        case 'getDemandesForUser':
            $result = getDemandesForUser($db, $params);
            break;
        case 'getDemandesForWorker':
            $result = getDemandesForWorker($db, $params);
            break;
        case 'addReview':
            $result = addReview($db, $params);
            break;
        default:
            header("HTTP/1.0 405 Method Not Allowed");
            break;
    }
    $result["req"] = $requestName;
    echo json_encode($result);

} catch (\Exception $exp) {
    echo json_encode(
        array(
            'status' => 'nok',
            'status_message' => 'unknown error'
        )
    );
}
?>