<?php
include_once "Common.php";

class Patch extends Common {

    protected $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

   // public function patchCampaign($body, $id) {
    //     try{
    //         $setClause = implode(", ", array_map(function ($key) {
    //             return "$key = ?";
    //         }, array_keys($body)));
            
    //         $sql = "UPDATE campaigns_tbl SET $setClause WHERE id = ?";
    //         $stmt = $this->pdo->prepare($sql);
    
    //         $values = array_values($body);
    //         $values[] = $id;
    
    //         $stmt->execute($values);

    //         $result = $this->getDataByTable('Campaigns_tbl', $id, $this->pdo);

    //         $this->logger($body['user_id'], "PATCH", "updated a campaign record");
    //         return $this->generateResponse($result['data'], "success", "Successfully updated a campaign.", 200);
    //     } 
    //     catch (\PDOException $e) {
    //         $this->logger($body['user_id'], "PATCH", $e->getMessage());
    //         return $this->generateResponse(null, "failed", $e->getMessage(), 400);
    //     }
    // }

    // public function archiveCampaign($id) {
    //     try {
    //         $sqlString = "UPDATE campaigns_tbl SET is_archived = 1 WHERE id = ?";
    //         $sql = $this->pdo->prepare($sqlString);
    //         $sql->execute([$id]);

    //         $this->logger($body['user_id'], "PATCH", "archived a campaign record");
    //         return $this->generateResponse(null, "success", "Successfully archive a campaign.", 200);
    //     } 
    //     catch (\PDOException $e) {
    //         $this->logger($body['user_id'], "PATCH", $e->getMessage());
    //         return $this->generateResponse(null, "failed", $e->getMessage(), 400);
    //     }
    // }

    // public function archivePledge($id) {
    //     try {
    //         $this->pdo->beginTransaction();
        
    //         $sql = "SELECT amount, campaign_id FROM Pledges_tbl WHERE id = ?";
    //         $stmt = $this->pdo->prepare($sql);
    //         $stmt->execute([$id]);
    //         $pledge = $stmt->fetch();
            
    //         if (!$pledge) {
    //             $this->pdo->rollBack();
    //             return $this->generateResponse(null, "failed", "Pledge not found.", 404);
    //         }

    //         $sqlString = "UPDATE pledges_tbl SET is_archived = 1 WHERE id = ?";
    //         $sql = $this->pdo->prepare($sqlString);
    //         $sql->execute([$id]);
        
    //         $updateSql = "UPDATE campaigns_tbl SET amount_raised = amount_raised - ? WHERE id = ?";
    //         $updateStmt = $this->pdo->prepare($updateSql);
    //         $updateStmt->execute([$pledge['amount'], $pledge['campaign_id']]);
    
    //         $this->pdo->commit();
    
    //         $this->logger($user_id, "DELETE", "Deleted a pledge and updated campaign amount raised.");
            
    //         return $this->generateResponse(null, "success", "Successfully deleted the pledge and updated the campaign.", 200);
    //     } catch (\PDOException $e) {
    //         $this->pdo->rollBack();
    //         $this->logger($user_id, "DELETE", $e->getMessage());

    //         return $this->generateResponse(null, "failed", $e->getMessage(), 400);
    //     }
    // }

  
}

?>
