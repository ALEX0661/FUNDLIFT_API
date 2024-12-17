<?php

include_once "Common.php";

class Post extends Common {

    protected $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function createCampaign($body) {
        $body['user_id'] = $this->getUserDetails()['user_id'];


        $result = $this->postData("campaigns_tbl", $body, $this->pdo);
        if ($result['code'] == 200) {
            $this->logger(null, null, null, "POST", "Created a new campaign record titled '{$body['title']}'.");
            return $this->generateResponse($result['data'], "success", "Successfully created a new campaign titled '{$body['title']}'.", $result['code']);
        }
        $this->logger(null, null, null, "POST", $result['errmsg']);
        return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
    }

    public function createPledge($body) {
        try {
            $body['user_id'] = $this->getUserDetails()['user_id'];

            $this->pdo->beginTransaction();
            $result = $this->postData("Pledges_tbl", $body, $this->pdo);
            if ($result['code'] !== 200) {
                $this->pdo->rollBack();
                $this->logger(null, null, null, "POST", $result['errmsg']);
                return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
            }
            $result1 = $this->executeQuery("UPDATE campaigns_tbl SET amount_raised = amount_raised + ? WHERE id = ?", [$body['amount'], $body['campaign_id']]);
    
            $this->pdo->commit();
            $this->logger(null, null, null, "POST", "Added a new pledge worth '{$body['amount']}' to the campaign with id: '{$body['campaign_id']}' and raised its amount to '{$result1['amount_raised']}'.");
            return $this->generateResponse($result['data'], "success", "Successfully added a new pledge worth '{$body['amount']}' to the campaign with id:'{$body['campaign_id']}' and raised its amount to '{$result1['amount_raised']}'.", 200);
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            $this->logger(null, null, null, "POST", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }
    


}

?>
