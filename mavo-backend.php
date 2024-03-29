<?php
	@session_start();
	$datas = file_get_contents('php://input');
	$status = false;
	$finalData = array();
	$isLogged = false;

	// Globally adding the logged state
	if (isset($_SESSION['user']) && $_SESSION['user']['isLogged']) {
		$isLogged = true;
	}

	// Function to know if local file exists, or if it can be created
	function data_exists($filePath = '') {
		if (trim($filePath) === '') {
			return false;
		}

		if (file_exists($filePath)) {
			$file = realpath($filePath);
			return is_writable($file);
		} else {
			return touch($filePath);
		}
	}

	// Defaults _GET
	if (!isset($_GET['source'])) {
		$_GET['source'] = '';
	}
	if (!isset($_GET['action'])) {
		$_GET['action'] = 'login';
	}

	switch ($_GET['action']) {
		case 'putFile': {
				if ($isLogged && data_exists($_GET['source'])) {
					// Upload a file
					if (isset($_GET['file']) && !empty($_GET['file'])) {
						// We got a filename
						$filename = $_GET['file'];
					} else {
						// We have to make a random name
						$filename = uniqid();
					}
					// Trying to sanitize filename with some light PHP
					$filename_san = filter_var($filename, FILTER_SANITIZE_URL);
					if ($filename_san !== false) {
						$filename = $filename_san;
					}

					if (isset($_GET['path'])) {
						// Path given, let's try to write to it
						$path = explode(DIRECTORY_SEPARATOR, $_GET['path']);
						array_pop($path);
						$finalPath = implode(DIRECTORY_SEPARATOR, $path);
						if (file_exists($finalPath) && is_dir($finalPath) && is_writeable($finalPath)) {
							// File path exists, is a dir and is writeable. Almost perfect !
							if (substr($finalPath, -1) === DIRECTORY_SEPARATOR) {
								// Remove the trailing sla...DIRECTORY_SEPARATOR
								$finalPath = substr($finalPath, 0, -1);
							}
						} else {
							// By default, the current dir
							$finalPath = __DIR__;
						}
					} else {
						// By default, the current dir
						$finalPath = __DIR__;
					}
					// Setting the final path
					$filename = $finalPath . DIRECTORY_SEPARATOR . $filename;

					// Find if file exists
					if (file_exists($filename)) {
						// Make a unique-ish name with a timestamp
						$fileInfo = pathinfo($filename);
						if (isset($fileInfo['extension'])) {
							// If we got the extension, we only keep file name before adding path
							$filename = $fileInfo['filename'];
						} else {
							// No extension ? Well, why not
							$fileInfo['extension'] = '';
						}
						// Then add the time()
						$filename = $filename . '-' . time() . '.' . $fileInfo['extension'];
						// Then the add filepath, again
						$filename = $finalPath . DIRECTORY_SEPARATOR . $filename;
					}
					// Write to server
					$status = file_put_contents($filename, base64_decode($datas));

					if ($status) {
						//Send back some info about file
						$fileInfo = stat($filename);
					} else {
						//Send empty info
						$fileInfo = array(
							'size' => 0,
							'type' => ''
						);
					}

					$finalData = array(
						'file' => $filename,
						// The truth is, I don't need it, but hum...you know, data, decisions, things...
						'size' => $fileInfo['size']
					);
				}
			}
			break;
		case 'putData': {
				if ($isLogged && data_exists($_GET['source'])) {
					$resWrite = file_put_contents($_GET['source'], $datas);
					if ($resWrite !== false) {
						$status = true;
					}
				} else {
					$finalData['debug'] = array(
						'isLogged' => $isLogged,
						'data_exists' => data_exists($_GET['source']),
						'source' => $_GET['source']
					);
				}
			}
			break;
		case 'login': {
				if ($isLogged) {
					// If user logged, send user data
					$finalData = $_SESSION['user'];
					$status = true;
				} else {
					// Return login form
					$finalData = array(
						'loginUrl' => './login.php?ref=' . $_SERVER['HTTP_REFERER']
					);
					$status = false;
				}
			}
			break;
		case 'logout': {
				if ($isLogged) {
					unset($_SESSION['user']);
				}
				$status = (!isset($_SESSION['user']));
			}
			break;
		default: {
				$finalData = array('action' => $_GET['action']);
				$status = false;
			}
			break;
	}
	echo json_encode(array(
		'status' => $status,
		'data' => $finalData
	));
