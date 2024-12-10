<?php
include_once "Common.php";

class Delete extends Common {
    protected $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function deleteCampaign($id) {
        try {
            $this->pdo->beginTransaction();

            $this->executeQuery("DELETE FROM pledges_tbl WHERE campaign_id = ?", [$id]);
            $this->executeQuery("DELETE FROM campaigns_tbl WHERE id = ?", [$id]);

            $this->pdo->commit();

            $this->logger($this->getUsername(), "DELETE", "Deleted the campaign record with ID: $id and all related pledges");
            return $this->generateResponse(null, "success", "Successfully deleted the campaign record with ID: $id along with all associated pledges", 200);
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            $this->logger($this->getUsername(), "DELETE", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }

    public function deletePledge($id) {
        try {
            $result = $this->executeQuery("SELECT is_archived, amount, campaign_id FROM Pledges_tbl WHERE id = ?", [$id]);
            $pledge = $result['data'][0];

            if (!$pledge) {
                return $this->generateResponse(null, "failed", "Pledge not found.", 404);
            }
            if ($pledge['is_archived'] == 0) {
                $this->logger($this->getUsername(), "DELETE", "failed to destroy a pledge recored");
                return $this->generateResponse(null, "failed", "Pledge with ID: $id is not archived thus cannot be deleted.", 400);
            }

            $this->executeQuery("DELETE FROM pledges_tbl WHERE id = ?", [$id]);

            $this->logger($this->getUsername(), "DELETE", "Deleted pledge with ID $id.");
            return $this->generateResponse(null, "success", "Pledge with ID $id was successfully deleted.", 200);
        } catch (\PDOException $e) {
            $this->logger($this->getUsername(), "DELETE", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }

}
?>
