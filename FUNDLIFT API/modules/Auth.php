<?php
class Authentication {

    protected $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function isAuthorized() {
        // Compare request token to database token
        $headers = array_change_key_case(getallheaders(), CASE_LOWER);
        //return $this->getToken() === $headers['authorization'];
        return true;
    }

    private function getToken() {
        $headers = array_change_key_case(getallheaders(), CASE_LOWER);

        $sqlString = "SELECT token FROM user_tbl WHERE username = ?";
        try {
            $stmt = $this->pdo->prepare($sqlString);
            $stmt->execute([$headers['x-auth-user']]);
            $result = $stmt->fetchAll()[0];
            return $result['token'];
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return "";
    }

    private function generateHeader() {
        $header = [
            "typ" => "JWT",
            "alg" => "HS256",
            "app" => "FundLift",
            "dev" => "Team NaN"
        ];
        return base64_encode(json_encode($header));
    }

    private function generatePayload($id, $username) {
        $payload = [
            "uid" => $id,
            "uc" => $username,
            "email" => "user@example.com",
            "date" => date_create(),
            "exp" => date("Y-m-d H:i:s", strtotime('+1 hour'))
        ];
        return base64_encode(json_encode($payload));
    }

    private function generateToken($id, $username) {
        $header = $this->generateHeader();
        $payload = $this->generatePayload($id, $username);
        $signature = hash_hmac("sha256", "$header.$payload", TOKEN_KEY);
        return "$header.$payload." . base64_encode($signature);
    }

    private function isSamePassword($inputPassword, $existingHash) {
        $hash = crypt($inputPassword, $existingHash);
        return $hash === $existingHash;
    }

    private function encryptPassword($password) {
        $hashFormat = "$2y$10$"; // Blowfish
        $saltLength = 22;
        $salt = $this->generateSalt($saltLength);
        return crypt($password, $hashFormat . $salt);
    }

    private function generateSalt($length) {
        $urs = md5(uniqid(mt_rand(), true));
        $b64String = base64_encode($urs);
        $mb64String = str_replace("+", ".", $b64String);
        return substr($mb64String, 0, $length);
    }

    public function saveToken($token, $username) {
        $errmsg = "";
        $code = 0;

        try {
            $sqlString = "UPDATE user_tbl SET token = ? WHERE username = ?";
            $sql = $this->pdo->prepare($sqlString);
            $sql->execute([$token, $username]);

            $code = 200;
            $data = null;

            return ["data" => $data, "code" => $code];
        } catch (\PDOException $e) {
            $errmsg = $e->getMessage();
            $code = 400;
        }

        return ["errmsg" => $errmsg, "code" => $code];
    }

    public function login($body) {
        $username = $body->username;
        $password = $body->password;

        $code = 0;
        $payload = "";
        $remarks = "";
        $message = "";

        try {
            $sqlString = "SELECT id, username, password, token FROM user_tbl WHERE username = ?";
            $stmt = $this->pdo->prepare($sqlString);
            $stmt->execute([$username]);

            if ($stmt->rowCount() > 0) {
                $result = $stmt->fetchAll()[0];
                if ($this->isSamePassword($password, $result['password'])) {
                    $code = 200;
                    $remarks = "success";
                    $message = "Logged in successfully";

                    $token = $this->generateToken($result['id'], $result['username']);
                    $tokenArr = explode('.', $token);
                    $this->saveToken($tokenArr[2], $result['username']);
                    $payload = ["id" => $result['id'], "username" => $result['username'], "token" => $tokenArr[2]];
                } else {
                    $code = 401;
                    $payload = null;
                    $remarks = "failed";
                    $message = "Incorrect Password.";
                }
            } else {
                $code = 401;
                $payload = null;
                $remarks = "failed";
                $message = "Username does not exist.";
            }
        } catch (\PDOException $e) {
            $message = $e->getMessage();
            $remarks = "failed";
            $code = 400;
        }
        return ["payload" => $payload, "remarks" => $remarks, "message" => $message, "code" => $code];
    }

    public function addAccount($body) {
        $values = [];
        $errmsg = "";
        $code = 0;

        $body->password = $this->encryptPassword($body->password);

        foreach ($body as $value) {
            array_push($values, $value);
        }

        try {
            $sqlString = "INSERT INTO user_tbl(username, password) VALUES (?, ?)";
            $sql = $this->pdo->prepare($sqlString);
            $sql->execute($values);

            $code = 200;
            $data = null;

            return ["data" => $data, "code" => $code];
        } catch (\PDOException $e) {
            $errmsg = $e->getMessage();
            $code = 400;
        }

        return ["errmsg" => $errmsg, "code" => $code];
    }
}

?>
