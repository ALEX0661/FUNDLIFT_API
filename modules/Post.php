<?php

include_once "Common.php";

class Post extends Common {

    protected $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function createCampaign($body) {
        $body['user_id'] = $this->getUserId();


        $result = $this->postData("campaigns_tbl", $body, $this->pdo);
        if ($result['code'] == 200) {
            $this->logger($this->getUsername(), $this->getUserId(), "POST", "Created a new campaign record");
            return $this->generateResponse($result['data'], "success", "Successfully created a new campaign.", $result['code']);
        }
        $this->logger($this->getUsername(), $this->getUserId(), "POST", $result['errmsg']);
        return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
    }

    public function createPledge($body) {
        try {
            $body['user_id'] = $this->getUserId();

            $this->pdo->beginTransaction();
            $result = $this->postData("Pledges_tbl", $body, $this->pdo);
            if ($result['code'] !== 200) {
                $this->pdo->rollBack();
                $this->logger($this->getUsername(), $this->getUserId(), "POST", $result['errmsg']);
                return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
            }
    
            $sql = "UPDATE campaigns_tbl SET amount_raised = amount_raised + ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$body['amount'], $body['campaign_id']]);
    
            $this->pdo->commit();
            $this->logger($this->getUsername(), $this->getUserId(), "POST", "Added a new pledge and updated the campaign's raised amount.");
            return $this->generateResponse($result['data'], "success", "Successfully added a new pledge and updated the campaign's raised amount.", 200);
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            $this->logger($this->getUsername(), $this->getUserId(), "POST", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }
    


}

?>
