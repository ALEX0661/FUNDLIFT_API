<?php

include_once "Common.php";

class Post extends Common {

    protected $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function createCampaign($body) {
        $body['user_id'] = $this->getUserDetails()['user_id'];

        if ($this->getUserDetails()['role'] == 'user') {
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
    
            $stmt = $this->executeQuery(
                "SELECT status FROM campaigns_tbl WHERE id = ?",
                [$body['campaign_id']]
            );
    
            if ($stmt['code'] !== 200 || empty($stmt['data'])) {
                return $this->generateResponse(
                    null,
                    "failed",
                    "Campaign with ID: {$body['campaign_id']} not found.",
                    404
                );
            }
    
            $campaignStatus = $stmt['data'][0]['status'];
            if ($campaignStatus !== 'active') {
                return $this->generateResponse(
                    null,
                    "failed",
                    "Pledges can only be made to campaigns with an 'active' status. The current status of this campaign is '$campaignStatus'.",
                    400
                );
            }
    
            // Restrict payment_status during pledge creation
            if (isset($body['payment_status'])) {
                return $this->generateResponse(
                    null,
                    "failed",
                    "You cannot set the payment status when creating a pledge. It defaults to 'pending'.",
                    400
                );
            }
    
            $result = $this->postData("Pledges_tbl", $body, $this->pdo);
            if ($result['code'] !== 200) {
                $this->pdo->rollBack();
                $this->logger(null, null, null, "POST", $result['errmsg']);
                return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
            }
    
            $this->logger(
                null,
                null,
                null,
                "POST",
                "Created a new pledge worth '{$body['amount']}' for the campaign with ID: '{$body['campaign_id']}'. Payment status is set to 'pending'."
            );
    
            return $this->generateResponse(
                $result['data'],
                "success",
                "Successfully created a new pledge worth '{$body['amount']}' for the campaign with ID: '{$body['campaign_id']}'. Payment status is 'pending'.",
                200
            );
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            $this->logger(null, null, null, "POST", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }
    

}

?>
