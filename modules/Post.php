<?php

include_once "Common.php";

class Post extends Common {

    protected $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function createCampaign($body) {
        $body['user_id'] = $this->getUserDetails()['user_id'];

        if ($this->getUserDetails()['role'] !== 'admin' && $this->getUserDetails()['role'] !== 'campaign_owner') {
            $this->logger(null, null, null, "POST", "Unathorized access. User failed to create a campaign");
            return $this->generateResponse(null, "failed", "Unathorized access. You do not have permission to create a campaign.", 403);
        }

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

            if ($this->getUserDetails()['role'] !== 'admin') {

                $result = $this->executeQuery("SELECT user_id FROM campaigns_tbl WHERE id = ?", [$body['campaign_id']]);
                $campaign = $result['data'][0] ?? null;
        
                if ($campaign && $campaign['user_id'] == $body['user_id']) {
                    $this->logger(null, null, null, "POST", "Unathorized access. Failed to pledge on their own campaign");
                    return $this->generateResponse(null, "failed", "Unathorized access. You cannot pledge to your own campaign.", 400);
                }
            }

            $this->pdo->beginTransaction();
            $result = $this->postData("Pledges_tbl", $body, $this->pdo);
            if ($result['code'] !== 200) {
                $this->pdo->rollBack();
                $this->logger(null, null, null, "POST", $result['errmsg']);
                return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
            }
            $this->executeQuery("UPDATE campaigns_tbl SET amount_raised = amount_raised + ? WHERE id = ?", [$body['amount'], $body['campaign_id']]);
            
            $result = $this->executeQuery("SELECT title, amount_raised, goal_amount FROM campaigns_tbl WHERE id = ?", [$body['campaign_id']]);
            $data = $result['data'][0]??null;

            $this->pdo->commit();

            $this->logger(null, null, null, "POST", "Pledge of '{$body['amount']}' added to campaign ID '{$body['campaign_id']}'. Updated amount raised: '{$data['amount_raised']}' out of the goal amount '{$data['goal_amount']}'.");            
            return $this->generateResponse($result['data'], "success",  "Pledge of '{$body['amount']}' successfully added to campaign ID '{$body['campaign_id']}'. The total amount raised is now '{$data['amount_raised']}' out of the goal amount '{$data['goal_amount']}'.", 200);
            
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            $this->logger(null, null, null, "POST", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }
    


}

?>
