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

            $this->logger($this->getUsername(), "PATCH", "Updated campaign record with ID: $id.");
            return $this->generateResponse($this->getDataByTable('Campaigns_tbl', $id, $this->pdo)['data'], "success", "Successfully updated the campaign record with ID: $id.", 200);
        } 
        catch (\PDOException $e) {
            $this->logger($this->getUsername(), "PATCH", $e->getMessage());
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
    
            $this->logger($this->getUsername(), "PATCH", "Archived campaign record with ID: $id and all associated pledges.");
            return $this->generateResponse(null, "success", "Successfully archived the campaign record with ID: $id and all associated pledges.", 200);
        } 
        catch (\PDOException $e) {
            $this->pdo->rollBack();
            $this->logger($this->getUsername(), "PATCH", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }
    
    public function archivePledge($id) {
        try {
            $this->pdo->beginTransaction();
            
            $result = $this->executeQuery("SELECT is_archived, amount, campaign_id FROM Pledges_tbl WHERE id = ?", [$id]);
            $pledge = $result['data'][0];
            if (!$pledge) {
                $this->pdo->rollBack();
                return $this->generateResponse(null, "failed", "Pledge not found.", 404);
            }
    
            if ($pledge['is_archived'] == 1) {
                $this->pdo->rollBack();
                return $this->generateResponse(null, "failed", "Pledge with ID: $id is already archived.", 400);
            }
    
            $this->executeQuery("UPDATE pledges_tbl SET is_archived = 1 WHERE id = ?", [$id]);
            $this->executeQuery("UPDATE campaigns_tbl SET amount_raised = amount_raised - ? WHERE id = ?",[$pledge['amount'], $pledge['campaign_id']] );
         
            $this->pdo->commit();
            $this->logger($this->getUsername(), "PATCH", "Archived pledge with ID $id and updated the campaign's raised amount.");
    
            return $this->generateResponse(null, "success", "Pledge with ID $id was successfully archived, and the campaign's total raised amount has been updated.", 200);
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            $this->logger($this->getUsername(), "PATCH", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }
    
  
}

?>
