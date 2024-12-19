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
                    if (isset($request[1])) {
                        // Get campaigns by ID, status, or owned campaigns
                        if ($request[1] === "owned") {
                            $dataString = json_encode($get->getOwnedCampaigns());
                        } elseif ($request[1] === "status" && isset($request[2])) {
                            $dataString = json_encode($get->getCampaigns(null, $request[2]));
                        } else {
                            $dataString = json_encode($get->getCampaigns($request[1]));
                        }
                    } else {
                        // Get all campaigns
                        $dataString = json_encode($get->getCampaigns());
                    }
                    echo $dataString;
                    break;
    
                case "pledges":
                    if (isset($request[1])) {
                        if ($request[1] === "mine") {
                            // Get user's own pledges
                            $dataString = json_encode($get->getOwnPledges());
                        } elseif ($request[1] === "status" && isset($request[2])) {
                            // Get pledges by status
                            $dataString = json_encode($get->getPledges(null, $request[2]));
                        } elseif ($request[1] === "refund_status" && isset($request[2])) {
                            // Get pledges by refund status
                            $dataString = json_encode($get->getPledges(null, null, $request[2]));
                        } elseif ($request[1] === "by_campaign" && isset($request[2])) {
                            // Get pledges by campaign ID
                            $dataString = json_encode($get->getPledges($request[2]));
                        } elseif ($request[1] === "by_user" && isset($request[2])) {
                            // Admin: Get pledges by user ID
                            if ($auth->getUserDetails()['role'] === 'admin') {
                                $dataString = json_encode($get->getPledgesByUserId($request[2]));
                            } else {
                                http_response_code(403);
                                echo json_encode(["status" => "failed", "message" => "Unauthorized access."]);
                                exit;
                            }
                        } else {
                            http_response_code(400);
                            echo json_encode(["status" => "failed", "message" => "Invalid pledges endpoint."]);
                            exit;
                        }
                    } else {
                        // Get all pledges
                        $dataString = json_encode($get->getPledges());
                    }
                    echo $dataString;
                    break;
    
                case "refund_requests":
                    if ($auth->getUserDetails()['role'] === 'admin') {
                        $dataString = json_encode($get->getRefundRequests());
                        echo $dataString;
                    } else {
                        http_response_code(403);
                        echo json_encode(["status" => "failed", "message" => "Unauthorized access."]);
                    }
                    break;
    
                case "payment_requests":
                    if ($auth->getUserDetails()['role'] === 'admin') {
                        $dataString = json_encode($get->getPaymentRequests());
                        echo $dataString;
                    } else {
                        http_response_code(403);
                        echo json_encode(["status" => "failed", "message" => "Unauthorized access."]);
                    }
                    break;
    
                case "logs":
                    echo json_encode($get->getLogs($request[1] ?? date("Y-m-d")));
                    break;
    
                default:
                    http_response_code(400);
                    echo json_encode(["status" => "failed", "message" => "Invalid endpoint."]);
                    break;
            }
        } else {
            http_response_code(401);
            echo json_encode(["status" => "failed", "message" => "Unauthorized access."]);
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
