<?php
include_once "Common.php";

class Patch extends Common {

    protected $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function patchCampaign($body, $id) {
        try {
            $userDetails = $this->getUserDetails();
            $userId = $userDetails['user_id'];
            $userRole = $userDetails['role'];
    
            $campaign = $this->executeQuery(
                "SELECT user_id, status FROM campaigns_tbl WHERE id = ?",
                [$id]
            );
    
            if ($campaign['code'] !== 200 || empty($campaign['data'])) {
                return $this->generateResponse(
                    null,
                    "failed",
                    "Campaign with ID: $id not found.",
                    404
                );
            }
    
            $campaignData = $campaign['data'][0];
            $campaignOwnerId = $campaignData['user_id'];
            $currentStatus = $campaignData['status'];
    
            if ($userRole !== 'admin' && $campaignOwnerId !== $userId) {
                return $this->generateResponse(
                    null,
                    "failed",
                    "Unauthorized access. Only admins and the campaign owner can update this campaign.",
                    403
                );
            }
    
            if ($currentStatus === 'archived') {
                return $this->generateResponse(
                    null,
                    "failed",
                    "Campaign record with ID: $id is archived and cannot be updated.",
                    400
                );
            }
    
            if (isset($body['status']) && !in_array($body['status'], ['active', 'completed'])) {
                return $this->generateResponse(
                    null,
                    "failed",
                    "Invalid status. Only 'active' or 'completed' statuses are allowed.",
                    400
                );
            }
    
            $setClause = implode(", ", array_map(function ($key) {
                return "$key = ?";
            }, array_keys($body)));
    
            $sql = "UPDATE campaigns_tbl SET $setClause WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $values = array_values($body);
            $values[] = $id;
            $stmt->execute($values);
    
            $updatedCampaign = $this->getDataByTable('campaigns_tbl', $id, $this->pdo);

            $this->logger(
                null,
                null,
                null,
                "PATCH",
                "Updated campaign record with ID: $id. Changes: " . json_encode($body)
            );
    
            return $this->generateResponse(
                $updatedCampaign,
                "success",
                "Successfully updated the campaign record with ID: $id.",
                200
            );
        } catch (\PDOException $e) {
            $this->logger(null, null, null, "PATCH", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }
    
    

    public function requestRemoveCampaign($id) {
        try {
            $userDetails = $this->getUserDetails();
            $userId = $userDetails['user_id'];
            $userRole = $userDetails['role'];
    
            // Fetch campaign details
            $stmt = $this->executeQuery(
                "SELECT user_id, status FROM campaigns_tbl WHERE id = ?",
                [$id]
            );
    
            if ($stmt['code'] !== 200 || empty($stmt['data'])) {
                return $this->generateResponse(
                    null,
                    "failed",
                    "Campaign with ID: $id not found.",
                    404
                );
            }
    
            $campaignData = $stmt['data'][0];
            $campaignOwnerId = $campaignData['user_id'];
            $status = $campaignData['status'];
    
            if ($userRole !== 'admin' && $campaignOwnerId !== $userId) {
                return $this->generateResponse(
                    null,
                    "failed",
                    "Unauthorized access. Only admins and the campaign owner can request campaign removal.",
                    403
                );
            }

            
            if ($status === 'archived') {
                return $this->generateResponse(
                    null,
                    "failed",
                    "Campaign with ID: $id is already archived and cannot be removed.",
                    400
                );
            }
    
            if ($status === 'completed') {
                return $this->generateResponse(
                    null,
                    "failed",
                    "Campaign with ID: $id is already completed and cannot be removed.",
                    400
                );
            }
    
            if ($status === 'pending_removal') {
                return $this->generateResponse(
                    null,
                    "failed",
                    "Campaign with ID: $id already has a pending removal request.",
                    400
                );
            }
    
            $this->pdo->beginTransaction();
            $this->executeQuery(
                "UPDATE campaigns_tbl SET status = 'pending_removal' WHERE id = ?",
                [$id]
            );
            $this->pdo->commit();
    
            $this->logger(
                null,
                null,
                null,
                "PATCH",
                "Campaign removal request created for campaign ID: $id."
            );
    
            return $this->generateResponse(
                null,
                "success",
                "Campaign removal request successfully created for campaign ID: $id.",
                200
            );
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            $this->logger(null, null, null, "PATCH", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }
    

    public function approveRemoveCampaign($id) {
        try {
            if ($this->getUserDetails()['role'] !== 'admin') {

                return $this->generateResponse(null, "failed", "Unauthorized access. Only admins can approve campaign removal.", 403);
            }
        
            $stmt = $this->executeQuery(
                "SELECT status FROM campaigns_tbl WHERE id = ?",
                [$id]
            );
    
            if ($stmt['code'] !== 200 || empty($stmt['data'])) {
                return $this->generateResponse(null, "failed", "Campaign with ID: $id not found.", 404);
            }
    
            $status = $stmt['data'][0]['status'];
            if ($status !== 'pending_removal') {
                return $this->generateResponse(null, "failed", "No pending removal request for campaign ID: $id.", 400);
            }
    
            $this->pdo->beginTransaction();
            $this->executeQuery("UPDATE campaigns_tbl SET status = 'archived' WHERE id = ?", [$id]);
            $this->pdo->commit();
    
            $this->logger(null, null, null, "PATCH", "Approved campaign removal for campaign ID: $id.");
            return $this->generateResponse(null, "success", "Successfully approved campaign removal for campaign ID: $id.", 200);
        } catch (\PDOException $e) {
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
            $result = $this->executeQuery("SELECT user_id FROM pledges_tbl WHERE id = ?", [$pledgeId]);
            $pledge = $result['data'][0] ?? null;
            
            if ($pledge && $pledge['user_id'] !== $userId) {
                return $this->generateResponse(null, "failed", "Unauthorized access. You can only request a refund for your own pledge.", 400);
            }
        }
    
        try {
            $pledge = $this->executeQuery("SELECT amount, refund_status, payment_status FROM pledges_tbl WHERE id = ?", [$pledgeId]);
    
            if (empty($pledge['data']) || $pledge['data'][0]['refund_status'] === 'refunded') {
                return $this->generateResponse(null, "failed", "Refund not possible. Invalid or already refunded pledge.", 400);
            }
    
            if ($pledge['data'][0]['payment_status'] !== 'paid') {
                return $this->generateResponse(null, "failed", "Refunds can only be requested for paid pledges.", 400);
            }
    
            if ($pledge['data'][0]['refund_status'] === 'not_requested') {
                $this->executeQuery("UPDATE pledges_tbl SET refund_status = 'pending', refund_reason = ? WHERE id = ?", [$refundReason, $pledgeId]);
    
                $this->logger(null, null, null, "POST", "User requested a refund for pledge ID '{$body['pledge_id']}' with reason: '{$body['refund_reason']}'");
    
                return $this->generateResponse(null, "success", "Refund request submitted successfully. Awaiting admin approval.", 200);
            } else {
                return $this->generateResponse(null, "failed", "Refund already requested or processed for this pledge.", 400);
            }
        } catch (\PDOException $e) {
            $this->logger(null, null, null, "PATCH", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }
    

    public function validateRefund($body) {
        try {
            if ($this->getUserDetails()['role'] !== 'admin') {

                return $this->generateResponse(null, "failed", "Unauthorized access. Only admins can vaidate refunds.", 403);
            }
            $pledgeId = $body['pledge_id'];
            $action = $body['action'];

            $pledge = $this->executeQuery("SELECT amount, campaign_id, refund_status FROM Pledges_tbl WHERE id = ?", [$pledgeId]);
    
            if (empty($pledge['data'])) {
                return $this->generateResponse(null, "failed", "Pledge not found.", 404);
            }
    
            if ($action === 'approve' && $pledge['data'][0]['refund_status'] === 'pending') {
                
                $this->executeQuery("UPDATE Pledges_tbl SET refund_status = 'refunded' WHERE id = ?", [$pledgeId]);

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
                return $this->generateResponse(null, "failed", "Invalid action for refund request.", 400);
            }
        } catch (\PDOException $e) {
            $this->logger(null, null, null, "PATCH", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }

    public function validatePayment($body) {
        try {
            if ($this->getUserDetails()['role'] !== 'admin') {

                return $this->generateResponse(null, "failed", "Unauthorized access. Only admins can vaidate payments.", 403);
            }
            $pledgeId = $body['pledge_id'];
            $action = $body['action'];
    
            $pledge = $this->executeQuery("SELECT user_id, amount, campaign_id, payment_status FROM Pledges_tbl WHERE id = ?", [$pledgeId]);
            $amount = $pledge['data'][0]??null;
    
            if (empty($pledge['data'])) {
                return $this->generateResponse(null, "failed", "Pledge not found.", 404);
            }
    
            if ($action === 'approve' && $pledge['data'][0]['payment_status'] === 'pending') {
                
                $this->executeQuery("UPDATE Pledges_tbl SET payment_status = 'paid' WHERE id = ?", [$pledgeId]);

                $this->executeQuery("UPDATE campaigns_tbl SET amount_raised = amount_raised + ? WHERE id = ?", 
                    [$pledge['data'][0]['amount'], $pledge['data'][0]['campaign_id']]);

                $result = $this->executeQuery("SELECT id, title, amount_raised, goal_amount FROM campaigns_tbl WHERE id = ?", [$pledge['data'][0]['campaign_id']]);
                $data = $result['data'][0]??null;

                $this->logger(null, null, null, "POST", "Admin approve Pledge of '{$amount['amount']}' added to campaign ID '{$data['id']}' by user id: '{$amount['user_id']}'. Updated amount raised: '{$data['amount_raised']}' out of the goal amount '{$data['goal_amount']}'.");            
                return $this->generateResponse($result['data'], "success",  "Pledge of '{$amount['amount']}' successfully added to campaign ID '{$data['id']}' by user id: '{$amount['user_id']}'. The total amount raised is now '{$data['amount_raised']}' out of the goal amount '{$data['goal_amount']}'.", 200);
            } elseif ($action === 'deny' && $pledge['data'][0]['payment_status'] === 'pending') {
                
                $this->executeQuery("UPDATE Pledges_tbl SET payment_status = 'unsuccessful' WHERE id = ?", [$pledgeId]);

                $this->logger(null, null, null, "PATCH", "Admin denied payment request for pledge ID '{$body['pledge_id']}'.");
                return $this->generateResponse(null, "success", "Payment was unsuccessful.", 200);
            } else {
                return $this->generateResponse(null, "failed", "Invalid action for refund request.", 400);
            }
        } catch (\PDOException $e) {
            $this->logger(null, null, null, "PATCH", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }


    
    
    
  
}

?>
