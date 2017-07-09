<?php
    @session_start();
    function login ($post) {
        if (isset($post['login'], $post['password'])) {
            $loginFile = file_get_contents('mavo-users.json');
            $loginData = json_decode($loginFile, true);
            foreach ($loginData['users'] as $log) {
                if ($log['login'] === $post['login']) {
                    if ($log['password'] === md5('mavo-'.$post['password'])) {
                        $_SESSION['user'] = $log;
                        $_SESSION['user']['isLogged'] = true;
                        unset($_SESSION['user']['password']);
                    }
                }
            }
        }
        return (isset($_SESSION['user']['isLogged']) && $_SESSION['user']['isLogged']);
    }
    
    if (isset($_POST) && !empty($_POST)) {
        if (login($_POST)) {
            $url = "./index.html";
            if ($_SESSION['previous']) {
                $url = $_SESSION['previous'];
                unset($_SESSION['previous']);
            }
            header('Location: '.$url);
        }
    } else {
        if (isset($_GET['ref'])) {
            $_SESSION['previous'] = $_GET['ref'];
        }
        readfile('login.html');
    }
