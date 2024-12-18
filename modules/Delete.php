<?php
include_once "Common.php";

class Delete extends Common {
    protected $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function deleteCampaign($id) {
        try {
            if ($this->getUserDetails()['role'] !== 'admin') {

                return $this->generateResponse(null, "failed", "Unauthorized access. Only admins can delete campaigns.", 403);
            }

            $pledge = $this->executeQuery("SELECT id FROM campaigns_tbl WHERE id = ?", [$id]);
            if (empty($pledge['data'])) {
                return $this->generateResponse(null, "failed", "Campaign not found.", 404);
            }

            $this->pdo->beginTransaction();

            $this->executeQuery("DELETE FROM pledges_tbl WHERE campaign_id = ?", [$id]);
            $this->executeQuery("DELETE FROM campaigns_tbl WHERE id = ?", [$id]);

            $this->pdo->commit();

            $this->logger(null, null, null, "DELETE", "Deleted the campaign record with ID: $id and all related pledges");
            return $this->generateResponse(null, "success", "Successfully deleted the campaign record with ID: $id along with all associated pledges", 200);
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            $this->logger(null, null, null, "DELETE", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }

    public function deletePledge($id) {
        try {
            if ($this->getUserDetails()['role'] !== 'admin') {
                return $this->generateResponse(null, "failed", "Unauthorized access. Only admins can delete pledges.", 403);
            }
    
            $pledge = $this->executeQuery("SELECT id FROM pledges_tbl WHERE id = ?", [$id]);
            if (empty($pledge['data'])) {
                return $this->generateResponse(null, "failed", "Pledge not found.", 404);
            }
    
            $this->executeQuery("DELETE FROM pledges_tbl WHERE id = ?", [$id]);
    
            $this->logger(null, null, null, "DELETE", "Deleted pledge with ID $id.");
    
            return $this->generateResponse(null, "success", "Pledge with ID $id was successfully deleted.", 200);
        } catch (\PDOException $e) {

            $this->logger(null, null, null, "DELETE", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }
    

}
?>
