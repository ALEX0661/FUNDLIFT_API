<?php
include_once "Common.php";

class Get extends Common {

    protected $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getLogs($date){
        $filename = "./logs/" . $date . ".log";
        
        $logs = array();
        try{
            $file = new SplFileObject($filename);
            while(!$file->eof()){
                array_push($logs, $file->fgets());
            }
            $remarks = "success";
            $message = "Successfully retrieved logs.";
        }
        catch(Exception $e){
            $remarks = "failed";
            $message = $e->getMessage();
        }
        

        return $this->generateResponse(array("logs"=>$logs), $remarks, $message, 200);
    }



    public function getCampaigns($id = null) {
        $condition = "is_archived = 0";
        if ($id !== null) {
            $condition .= " AND id=" . $id;
        }

        $result = $this->getDataByTable('Campaigns_tbl', $condition, $this->pdo);
        if ($result['code'] == 200) {
            return $this->generateResponse($result['data'], "success", "Successfully retrieved records.", $result['code']);
        }
        return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
    }

    public function getPledges($campaign_id = null) {
        $condition = "1=1";
        if ($campaign_id !== null) {
            $condition .= " AND campaign_id=" . $campaign_id;
        }

        $result = $this->getDataByTable('Pledges_tbl', $condition, $this->pdo);
        if ($result['code'] == 200) {
            return $this->generateResponse($result['data'], "success", "Successfully retrieved records.", $result['code']);
        }
        return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
    }
}
?>
