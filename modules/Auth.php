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
                
                return $this->generateResponse(null, "success", "Token updated successfully.", 200);
            } catch (\PDOException $e) {
                return $this->generateResponse(null, "failed", $e->getMessage(), 400);
            }
        }
        

    public function login($body) {
        try {
            $result = $this->executeQuery("SELECT id, username, password, token FROM user_tbl WHERE username = ?", [$body['username']]);
            $user = $result['data'][0]??null;

            if ($result['code'] == 200) {
    
                if ($this->isSamePassword($body['password'], $user['password'])) {
                    $token = $this->generateToken($user['id'], $user['username']);
                    $tokenArr = explode('.', $token);
                    $this->saveToken($tokenArr[2], $user['username']);
    
                    $this->logger(null, null, null, "POST", "'{$body['username']}' Logged in successfully.");
                    $payload = ["id" => $user['id'], "username" => $user['username'], "token" => $tokenArr[2]];
                    return $this->generateResponse($payload, "success", "Logged in successfully", 200);
                } else {
                    $this->logger(null, null, null, "POST", "Attempted login with invalid passwoed.");
                    return $this->generateResponse(null, "failed", "Incorrect Password.", 401);
                }
            } else {
                $this->logger(null, null, null, "POST", "Attempted login with invalid username.");
                return $this->generateResponse(null, "failed", "Username does not exist.", 401);
            }
        } catch (\PDOException $e) {
            $this->logger(null, null, null, "POST", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }

    public function addAccount($body) {
        $body['role'] = isset($body['role']) ? $body['role'] : 'user';
    
        $validRoles = ['admin', 'campaign_owner', 'user'];
        if (!in_array($body['role'], $validRoles)) {
            $this->logger(null, null, null, "POST", "Failed attempt to assign an invalid role: '{$body['role']}'", 403);
            return $this->generateResponse(null, "failed", "Invalid role specified.", 400);
        }

        if ($body['role'] !== 'user') {
            if ($this->getUserDetails()['role'] !== 'admin') {
                $this->logger(null, null, null, "POST", "Unauthorized attempt to assign the role '{$body['role']}'. Only admins can assign roles.", 403);
                return $this->generateResponse(null, "failed", "Only admins can assign roles.", 403);
            }
        }
    
        $body['password'] = $this->encryptPassword($body['password']);
    
        try {
            $result = $this->postData("user_tbl", $body, $this->pdo);
    
            $this->logger(null, null, null,"POST", "Created an account with '{$body['username']}' username and '{$body['role']}' assigned role.");
            return $this->generateResponse(null, "success", "Account successfully created.", 200);
        } catch (\PDOException $e) {
            $this->logger(null, null, null, "POST", $e->getMessage());
            return $this->generateResponse(null, "failed", $e->getMessage(), 400);
        }
    }
    
}    

?>
