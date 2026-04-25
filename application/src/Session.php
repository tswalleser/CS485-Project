<?php

namespace App;

session_start();

class Session {
    private static $session = null;

    private function __construct() {}

    public static function getSession() {
        if (self::$session === null) {
            self::$session = new self();
        }

        return self::$session;
    }

    public function login($user_id, $username) {
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
    }

    public function logout() {
        $_SESSION['logged_in'] = false;

        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        session_destroy();
    }

    public function is_logged_in() {
        return $_SESSION['logged_in'] ?? false;
    }

    public function save_report() {
        
    }
}

?>
