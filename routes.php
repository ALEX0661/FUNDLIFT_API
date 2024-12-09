<?php

// Import required files
require_once "./config/database.php";
require_once "./modules/Get.php";
require_once "./modules/Post.php";
require_once "./modules/Patch.php";
require_once "./modules/Delete.php";
require_once "./modules/Auth.php";
require_once "./modules/Crypt.php";

$db = new Connection();
$pdo = $db->connect();


$post = new Post($pdo);
$patch = new Patch($pdo);
$get = new Get($pdo);
$delete = new Delete($pdo);
$auth = new Authentication($pdo);
$crypt = new Crypt();

if (isset($_REQUEST['request'])) {
    $request = explode("/", $_REQUEST['request']);
} else {
    echo "URL does not exist.";
    exit;
}


switch ($_SERVER['REQUEST_METHOD']) {

    case "GET":
        if ($auth->isAuthorized()) {
            switch ($request[0]) {

                case "campaigns":
                    $dataString = json_encode($get->getCampaigns($request[1] ?? null));
                    echo $dataString;

                    break;

                case "pledges":
                    $dataString = json_encode($get->getPledges($request[1] ?? null));
                    echo $dataString;
                    break;

                case "logs":
                    echo json_encode($get->getLogs($request[1] ?? date("Y-m-d")));
                break;

                default:
                    http_response_code(401);
                    echo "Invalid endpoint.";
                    break;

            }
        } else {
            http_response_code(401);
            echo "Unauthorized.";
        }
        break;

    case "POST":
        $body = json_decode(file_get_contents("php://input"), true);
        switch ($request[0]) {

            case "login":
                echo json_encode($auth->login($body));
                break;

            case "register":
                echo json_encode($auth->addAccount($body));
                break;

            case "postcampaign":
                echo json_encode($post->createCampaign($body));
                break;

            case "postpledge":
                echo json_encode($post->createPledge($body));
                break;

            case "updatecampaign":
               echo json_encode($patch->patchCampaign($body, $request[1]));
               break;

            default:
                http_response_code(401);
                echo "Invalid endpoint.";
                break;
        }
        break;

    case "PATCH":
        $body = json_decode(file_get_contents("php://input"), true);
        switch ($request[0]) {

            case "updatecampaign":
                echo json_encode($patch->patchCampaign($body, $request[1]));
                break;

            case "archivecampaign":
                echo json_encode($patch->archiveCampaign($request[1]));
                break;

            case "archivepledge":
                echo json_encode($patch->archivePledge($request[1]));
                break;

            default:
                http_response_code(401);
                echo "Invalid endpoint.";
                break;
        }
        break;

    case "DELETE":
        switch ($request[0]) {
            case "delcampaign":
                echo json_encode($delete->deleteCampaign($request[1]));
                break;

            case "delpledge":
                echo json_encode($delete->deletePledge($request[1]));
                break;

            default:
                http_response_code(401);
                echo "Invalid endpoint.";
                break;
        }
        break;

    default:
        http_response_code(400);
        echo "Invalid Request Method.";
        break;
}

// Close database connection
$pdo = null;
