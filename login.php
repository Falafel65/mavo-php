<?php
	@session_start();

	function login($post) {
		if (isset($post['login'], $post['password'])) {
			$jsonUserFile = 'mavo-users.json';
			if (!file_exists($jsonUserFile)) {
				// Can't log if there is no users file
				return false;
			}
			$loginFile = file_get_contents($jsonUserFile);
			$loginData = json_decode($loginFile, true);
			if (is_null($loginData)) {
				// User file is not a valid json to PHP
				return false;
			}
			foreach ($loginData['users'] as $log) {
				if ($log['login'] === $post['login']) {
					if ($log['password'] === md5('mavo-' . $post['password'])) {
						$_SESSION['user'] = $log;
						$_SESSION['user']['isLogged'] = true;
						// Do not expose password hash more than necessary
						unset($_SESSION['user']['password']);
					}
				}
			}
		}
		return (isset($_SESSION['user']['isLogged']) && $_SESSION['user']['isLogged']);
	}

	if (isset($_POST) && !empty($_POST)) {
		if (login($_POST)) {
			$url = './index.html';
			if ($_SESSION['previous']) {
				$url = $_SESSION['previous'];
				unset($_SESSION['previous']);
			}
			header('Location: ' . $url);
		} else {
			header('Location: ' . $_SERVER['PHP_SELF'] . '?error');
		}
	} else {
		if (isset($_GET['ref'])) {
			$_SESSION['previous'] = $_GET['ref'];
		}
		$loginPage = file_get_contents('login.html');
		if (isset($_GET['error'])) {
			$loginPage = str_replace('</h1>', '</h1><div class="alert alert-danger">Error in your login</div>', $loginPage);
		}
		echo $loginPage;
	}
