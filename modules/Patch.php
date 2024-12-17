<?php
include_once "Common.php";

class Patch extends Common {

    protected $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

   public function patchCampaign($body, $id) {
        try{
            if ($this->checkIfArchived('campaigns_tbl', $id)) {
                return $this->generateResponse(null, "failed", "Campaign record with ID: $id is already archived.", 400);
            }

            $setClause = implode(", ", array_map(function ($key) {
                return "$key = ?";
            }, array_keys($body)));
            
            $sql = "UPDATE campaigns_tbl SET $setClause WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $values = array_values($body);
            $values[] = $id;
    
            $stmt->execute($values);

            $this->logger(null, null, null, "PATCH", "Updated campaign record with ID: $id.");
            return $this->generateResponse($this->getDataByTable('Campaigns_tbl', $id, $this->pdo), "success", "Successfully updated the campaign record with ID: $id.", 200);
        } 
        catch (\PDOException $e) {
            $this->logger(null, null, null, "PATCH", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }

    public function archiveCampaign($id) {
        try {
            if ($this->checkIfArchived('campaigns_tbl', $id)) {
                return $this->generateResponse(null, "failed", "Campaign record with ID: $id is already archived.", 400);
            }
    
            $this->pdo->beginTransaction();
    
            $this->executeQuery("UPDATE pledges_tbl SET is_archived = 1 WHERE campaign_id = ?", [$id]);
            $this->executeQuery("UPDATE campaigns_tbl SET is_archived = 1 WHERE id = ?", [$id]);

            $this->pdo->commit();
    
            $this->logger(null, null, null, "PATCH", "Archived campaign record with ID: $id and all associated pledges.");
            return $this->generateResponse(null, "success", "Successfully archived the campaign record with ID: $id and all associated pledges.", 200);
        } 
        catch (\PDOException $e) {
            $this->pdo->rollBack();
            $this->logger(null, null, null, "PATCH", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }
    
    public function requestRefund($body) {
        $userId = $this->getUserDetails()['user_id'];
        $pledgeId = $body['pledge_id'];
        $refundReason = $body['refund_reason'];

        if ($this->getUserDetails()['role'] !== 'admin') {

            $result = $this->executeQuery("SELECT user_id FROM campaigns_tbl WHERE id = ?", [$body['pledge_id']]);
            $pledge = $result['data'][0] ?? null;
    
            if ($pledge && $pledge['user_id'] !== $userId['user_id']) {
                $this->logger(null, null, null, "POST", "Unathorized access. Failed to refund on other user's pledge record");
                return $this->generateResponse(null, "failed", "Unathorized access. You can only refund your own pledge.", 400);
            }
        }
    
        try {
            $pledge = $this->executeQuery("SELECT amount, refund_status FROM pledges_tbl WHERE id = ?", [$pledgeId]);
    
            if (empty($pledge['data']) || $pledge['data'][0]['refund_status'] === 'refunded') {
                $this->logger(null, null, null, "POST", "User attempted to request a refund for pledge ID '{$body['pledge_id']}' but a pledge was already refunded.");
                return $this->generateResponse(null, "failed", "Refund not possible. Invalid or already refunded pledge.", 400);
            }
    
            if ($pledge['data'][0]['refund_status'] === 'not_requested') {
                $this->executeQuery("UPDATE pledges_tbl SET refund_status = 'pending', refund_reason = ? WHERE id = ?", [$refundReason, $pledgeId]);

                $this->logger(null, null, null, "POST", "User requested a refund for pledge ID '{$body['pledge_id']}' with reason: '{$body['refund_reason']}'");
                return $this->generateResponse(null, "success", "Refund request submitted successfully. Awaiting admin approval.", 200);
            } else {
                $this->logger(null, null, null, "POST", "User attempted to request a refund for pledge ID '{$body['pledge_id']}' but a refund has already been requested or processed.");
                return $this->generateResponse(null, "failed", "Refund already requested or processed for this pledge.", 400);
            }
        } catch (\PDOException $e) {
            $this->logger(null, null, null, "PATCH", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }

    public function approveRefund($body) {
        $pledgeId = $body['pledge_id'];
        $action = $body['action'];
    
        try {
            if ($this->getUserDetails()['role'] !== 'admin') {

                $this->logger(null, null, null, "PATCHS", "Failed to validate a refund request due to unauthorized access");
                return $this->generateResponse(null, "failed", "Unauthorized access. Only admins can vaidate refunds.", 403);
            }
            $pledge = $this->executeQuery("SELECT amount, campaign_id, refund_status FROM Pledges_tbl WHERE id = ?", [$pledgeId]);
    
            if (empty($pledge['data'])) {
                return $this->generateResponse(null, "failed", "Pledge not found.", 404);
            }
    
            // If refund is approved, update the pledge and campaign data
            if ($action === 'approve' && $pledge['data'][0]['refund_status'] === 'pending') {
                // Update pledge status
                $this->executeQuery("UPDATE Pledges_tbl SET refund_status = 'refunded' WHERE id = ?", [$pledgeId]);
    
                // Update campaign's amount_raised
                $this->executeQuery("UPDATE campaigns_tbl SET amount_raised = amount_raised - ? WHERE id = ?", 
                    [$pledge['data'][0]['amount'], $pledge['data'][0]['campaign_id']]);

                $this->logger(null, null, null, "PATCH", "Admin approved refund for pledge ID '{$body['pledge_id']}'. Amount refunded: '{$pledge['data'][0]['amount']}'.");
                return $this->generateResponse(null, "success", "Refund successfully processed for pledge ID '{$body['pledge_id']}'. Amount refunded: '{$pledge['data'][0]['amount']}'..", 200);
            } elseif ($action === 'deny') {
                // If denied, update the status to 'denied'
                $this->executeQuery("UPDATE Pledges_tbl SET refund_status = 'denied' WHERE id = ?", [$pledgeId]);

                $this->logger(null, null, null, "PATCH", "Admin denied refund request for pledge ID '{$body['pledge_id']}' with reason: '{$body['action']}'.");
                return $this->generateResponse(null, "success", "Refund request denied.", 200);
            } else {
                $this->logger(null, null, null, "PATCH", "Admin use invalid action on '{$body['pledge_id']}'.");
                return $this->generateResponse(null, "failed", "Invalid action for refund request.", 400);
            }
        } catch (\PDOException $e) {
            $this->logger(null, null, null, "PATCH", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }
    
    
    
  
}

?>
