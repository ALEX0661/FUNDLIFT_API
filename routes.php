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
                    //echo $crypt->encryptData($dataString);
                    break;

                case "pledges":
                    $dataString = json_encode($get->getPledges($request[1] ?? null));
                    echo $dataString;
                    //echo $crypt->encryptData($dataString);
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
            echo "Unauthorized access.";
        }
        break;

    case "POST":
        case "POST":
            $body = json_decode(file_get_contents("php://input"), true);
        
            switch ($request[0]) {
                case "login":
                    echo json_encode($auth->login($body));
                    break;
        
                case "register":
                    echo json_encode($auth->addAccount($body));
                    break;
        
                default:

                    if ($auth->isAuthorized()) {
                        switch ($request[0]) {
                            case "decrypt":
                                echo $crypt->decryptData($body);
                                break;
        
                            case "postcampaign":
                                echo json_encode($post->createCampaign($body));
                                break;
        
                            case "postpledge":
                                echo json_encode($post->createPledge($body));
                                break;
        
                            default:
                                http_response_code(401);
                                echo "Invalid endpoint.";
                                break;
                        }
                    } else {
                        // If user is not authorized
                        http_response_code(401);
                        echo "Unauthorized access.";
                    }
                    break;
            }
            break;
        
    case "PATCH":
        $body = json_decode(file_get_contents("php://input"), true);
        if ($auth->isAuthorized()) {
            switch ($request[0]) {

                case "updatecampaign":
                    echo json_encode($patch->patchCampaign($body, $request[1]));
                    break;

                case "removecampaign":
                    echo json_encode($patch->requestRemoveCampaign($request[1]));
                    break;

                case "approveremovecampaign":
                    echo json_encode($patch->approveRemoveCampaign($request[1]));
                    break;

                case "refund":
                    echo json_encode($patch->requestRefund($body));
                    break;

                case "valrefund":
                    echo json_encode($patch->validateRefund($body));
                    break;

                case "valpayment":
                    echo json_encode($patch->validatePayment($body));
                    break;

                default:
                    http_response_code(401);
                    echo "Invalid endpoint.";
                    break;
            }
        } else {
            http_response_code(401);
            echo "Unauthorized access.";
        }

        break;

    case "DELETE":
        if ($auth->isAuthorized()) {
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
            
        } else {
            http_response_code(401);
            echo "Unauthorized access.";
        }

        break;

    default:
        http_response_code(400);
        echo "Invalid Request Method.";
        break;
}

// Close database connection
$pdo = null;
