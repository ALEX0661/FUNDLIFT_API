<?php
include_once "Common.php";

class Authentication extends Common {
    
    protected $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function isAuthorized() {

        $headers = array_change_key_case(getallheaders(), CASE_LOWER);
        return $this->getToken() === $headers['authorization'];
    }

    private function getToken() {
        $headers = array_change_key_case(getallheaders(), CASE_LOWER);

        try {
            $stmt = $this->executeQuery("SELECT token FROM user_tbl WHERE username = ?",[$headers['x-auth-user']]);
            if ($stmt['code'] == 200 && isset($stmt['data'][0]['token'])) {
                return $stmt['data'][0]['token'];
            }
            return null;
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
            try {
                $this->executeQuery("UPDATE user_tbl SET token = ? WHERE username = ?",[$token, $username]);
                
                $this->logger($username, null, "POST", "Token saved successfully.");
                return $this->generateResponse(null, "success", "Token updated successfully.", 200);
            } catch (\PDOException $e) {
                $this->logger($username, null, "POST", "Failed to save token: " . $e->getMessage());
                return $this->generateResponse(null, "failed", $e->getMessage(), 400);
            }
        }
        

    public function login($body) {
        try {
            $result = $this->executeQuery("SELECT id, username, password, token FROM user_tbl WHERE username = ?", [$body['username']]);
            $user = $result['data'][0];
            if ($result['code'] == 200) {
    
                if ($this->isSamePassword($body['password'], $user['password'])) {
                    $token = $this->generateToken($user['id'], $user['username']);
                    $tokenArr = explode('.', $token);
                    $this->saveToken($tokenArr[2], $user['username']);
    
                    $this->logger($body['username'], $this->getUserId(), "POST", "Login successful.");
                    $payload = ["id" => $user['id'], "username" => $user['username'], "token" => $tokenArr[2]];
                    return $this->generateResponse($payload, "success", "Logged in successfully", 200);
                } else {
                    $this->logger($body['username'], $this->getUserId(), "POST", "Incorrect Password.");
                    return $this->generateResponse(null, "failed", "Incorrect Password.", 401);
                }
            } else {
                $this->logger($body['username'], $this->getUserId(), "POST", "Username does not exist.");
                return $this->generateResponse(null, "failed", "Username does not exist.", 401);
            }
        } catch (\PDOException $e) {
            $this->logger($body['username'], $this->getUserId(), "POST", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }
    

    public function addAccount($body) {
        $body['password'] = $this->encryptPassword($body['password']);
        $response = $this->postData('user_tbl', $body, $this->pdo);
    
        if ($response['code'] === 200) {
            $this->logger($body['username'], null, "POST", "Account added successfully.");
            return $this->generateResponse(null, "success", "Account created successfully.", 200);
        } else {
            $this->logger($body['username'], null, "POST", "Failed to add account: " . $response['errmsg']);
            return $this->generateResponse(null, "failed", $response['errmsg'], 400);
        }
    }
}    

?>
