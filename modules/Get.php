<?php
include_once "Common.php";

class Get extends Common {

    protected $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getLogs($date){
        $filename = "./logs/" . $date . ".log";
        
        $logs = array();
        try{
            $file = new SplFileObject($filename);
            while(!$file->eof()){
                array_push($logs, $file->fgets());
            }
            $remarks = "success";
            $message = "Successfully retrieved logs.";
        }
        catch(Exception $e){
            $remarks = "failed";
            $message = $e->getMessage();
        }
        

        return $this->generateResponse(array("logs"=>$logs), $remarks, $message, 200);
    }

    public function getCampaigns($id = null, $status = null) {
        try {
            $condition = "status != 'archived'";
    
            if ($id !== null) {
                $condition .= " AND id = ?";
                $params[] = $id;
            }
    
            if ($status !== null) {
                $condition .= " AND status = ?";
                $params[] = $status;
            }
    
            $result = $this->getDataByTable('campaigns_tbl', $condition, $this->pdo, $params ?? []);
    
            if ($result['code'] === 200) {
                return $this->generateResponse($result['data'], "success", "Successfully retrieved campaigns.", 200);
            }
            return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
        } catch (\PDOException $e) {
            $this->logger(null, null, null, "GET", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }
    
    public function getOwnedCampaigns() {
        try {
            $userId = $this->getUserDetails()['user_id'];
            $result = $this->executeQuery(
                "SELECT * FROM campaigns_tbl WHERE status != 'archived' AND user_id = ?",
                [$userId]
            );
    
            if ($result['code'] === 200) {
                return $this->generateResponse($result['data'], "success", "Successfully retrieved owned campaigns.", 200);
            }
            return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
        } catch (\PDOException $e) {
            $this->logger(null, null, null, "GET", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }
    
    public function getPledges($campaign_id = null, $status = null, $refund_status = null) {
        try {
            $condition = "1=1";
            $params = [];
    
            if ($campaign_id !== null) {
                $condition .= " AND campaign_id = ?";
                $params[] = $campaign_id;
            }
    
            if ($status !== null) {
                $condition .= " AND payment_status = ?";
                $params[] = $status;
            }
    
            if ($refund_status !== null) {
                $condition .= " AND refund_status = ?";
                $params[] = $refund_status;
            }
    
            $result = $this->getDataByTable('pledges_tbl', $condition, $this->pdo, $params);
    
            if ($result['code'] === 200) {
                return $this->generateResponse($result['data'], "success", "Successfully retrieved pledges.", 200);
            }
            return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
        } catch (\PDOException $e) {
            $this->logger(null, null, null, "GET", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }
    
    public function getOwnPledges() {
        try {
            $userId = $this->getUserDetails()['user_id'];
            $result = $this->executeQuery(
                "SELECT * FROM pledges_tbl WHERE user_id = ?",
                [$userId]
            );
    
            if ($result['code'] === 200) {
                return $this->generateResponse($result['data'], "success", "Successfully retrieved user pledges.", 200);
            }
            return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
        } catch (\PDOException $e) {
            $this->logger(null, null, null, "GET", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }
    
    public function getRefundRequests() {
        try {
            if ($this->getUserDetails()['role'] !== 'admin') {
                return $this->generateResponse(null, "failed", "Unauthorized access. Only admins can view refund requests.", 403);
            }
    
            $result = $this->executeQuery(
                "SELECT * FROM pledges_tbl WHERE refund_status = 'pending'"
            );
    
            if ($result['code'] === 200) {
                return $this->generateResponse($result['data'], "success", "Successfully retrieved refund requests.", 200);
            }
            return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
        } catch (\PDOException $e) {
            $this->logger(null, null, null, "GET", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }
    
    public function getPaymentRequests() {
        try {
            if ($this->getUserDetails()['role'] !== 'admin') {
                return $this->generateResponse(null, "failed", "Unauthorized access. Only admins can view payment requests.", 403);
            }
    
            $result = $this->executeQuery(
                "SELECT * FROM pledges_tbl WHERE payment_status = 'pending'"
            );
    
            if ($result['code'] === 200) {
                return $this->generateResponse($result['data'], "success", "Successfully retrieved payment requests.", 200);
            }
            return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
        } catch (\PDOException $e) {
            $this->logger(null, null, null, "GET", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }
    
}
?>
