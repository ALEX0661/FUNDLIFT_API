<?php
class Common {

    protected function logger($user, $method, $action) {
        // Log user activity to a file
        $filename = date("Y-m-d") . ".log";
        $datetime = date("Y-m-d H:i:s");
        $logMessage = "$datetime,$method,$user,$action" . PHP_EOL;
        error_log($logMessage, 3, "./logs/$filename");
    }

    private function generateInsertString($tablename, $body) {
        $keys = array_keys($body);
        $fields = implode(",", $keys);
        $parameter_array = array_fill(0, count($keys), "?");
        $parameters = implode(',', $parameter_array);
        $sql = "INSERT INTO $tablename($fields) VALUES ($parameters)";
        return $sql;
    }

    protected function getDataByTable($tableName, $condition, \PDO $pdo) {
        $sqlString = "SELECT * FROM $tableName WHERE $condition";
        $data = [];
        $errmsg = "";
        $code = 0;

        try {
            if ($result = $pdo->query($sqlString)->fetchAll()) {
                $data = $result;
                $code = 200;
                return ["code" => $code, "data" => $data];
            } else {
                $errmsg = "No data found";
                $code = 404;
            }
        } catch (\PDOException $e) {
            $errmsg = $e->getMessage();
            $code = 403;
        }

        return ["code" => $code, "errmsg" => $errmsg];
    }

    protected function getDataBySQL($sqlString, \PDO $pdo) {
        $data = [];
        $errmsg = "";
        $code = 0;

        try {
            if ($result = $pdo->query($sqlString)->fetchAll()) {
                $data = $result;
                $code = 200;
                return ["code" => $code, "data" => $data];
            } else {
                $errmsg = "No data found";
                $code = 404;
            }
        } catch (\PDOException $e) {
            $errmsg = $e->getMessage();
            $code = 403;
        }

        return ["code" => $code, "errmsg" => $errmsg];
    }

    protected function generateResponse($data, $remark, $message, $statusCode) {
        $status = [
            "remark" => $remark,
            "message" => $message
        ];

        http_response_code($statusCode);

        return [
            "payload" => $data,
            "status" => $status,
            "prepared_by" => "Team NaN",
            "date_generated" => date("Y-m-d H:i:s")
        ];
    }

    public function postData($tableName, $body, \PDO $pdo) {
        $values = array_values($body);
        $errmsg = "";
        $code = 0;

        try {
            $sqlString = $this->generateInsertString($tableName, $body);
            $sql = $pdo->prepare($sqlString);
            $sql->execute($values);
            $code = 200;

            return ["data" => null, "code" => $code];
        } catch (\PDOException $e) {
            $errmsg = $e->getMessage();
            $code = 400;
        }

        return ["errmsg" => $errmsg, "code" => $code];
    }
}
?>
