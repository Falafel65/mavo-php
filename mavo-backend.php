<?php
    @session_start();
    $datas = file_get_contents('php://input');
    $status = false;
    $finalData = array();
    
    //Function to know if local file exists, or if it can be created
    function data_exists ($filePath = '') {
        if ($filePath === '') {
            return false;
        }
        
        $file = realpath($filePath);
        if (file_exists($file)) {
            return is_writable($file);
        } else {
            return touch($file);
        }
    }
    
    //Defaults _GET
    if (!isset($_GET['source'])) {
        $_GET['source'] = '';
    }
    if (!isset($_GET['action'])) {
        $_GET['action'] = 'login';
    }
    
    switch ($_GET['action']) {
        case 'putFile': {
            if (data_exists($_GET['source'])) {
                //Upload a file
                if (isset($_GET['file']) && !empty($_GET['file'])) {
                    //We got a filename
                    $filename = $_GET['file'];
                } else {
                    //We have to make a random name
                    $filename = uniqid();
                }
                if (isset($_GET['path'])) {
                    //Path given, let's try to write to it
                    $path = explode(DIRECTORY_SEPARATOR, $_GET['path']);
                    array_pop($path);
					$finalPath = implode(DIRECTORY_SEPARATOR, $path);
					if (file_exists($finalPath) && is_dir($finalPath) && is_writeable($finalPath)) {
                    	//File path exists, is a dir and is writeable. Perfect !
						$filename = $finalPath.DIRECTORY_SEPARATOR.$filename;
					}
                }
				//Find if file exists
				if (file_exists($filename)) {
					//Make a unique-ish name with a timestamp
					$fileInfo = pathinfo($filename, PATHINFO_EXTENSION);
					if (isset($fileInfo['extension'])) {
						//If we got the extension, we remove it before adding path
						$filename = substr($filename, 0, -(strlen($fileInfo['extension']) + 1));
					} else {
						//No extension ? Well, why not
						$fileInfo['extension'] = '';
					}
					//Then add the time()
					$filename = $filename."-".time().".".$fileInfo['extension'];
				}
                //Write to server
                $status = file_put_contents($filename, base64_decode($datas));
                
                if ($status) {
                    //Send back some info about file
                    $fileInfo = stat($filename);
                } else {
                    //Send empty info
                    $fileInfo = array(
                        'size'=> 0,
                        'type'=> ''
                    );    
                }
                
                $finalData = array(
                    'file'=> $filename,
                    //The truth is, I don't need it, but hum...you know, data, decisions, things...
                    'size'=> $fileInfo['size']
                );
            }
        }
        break;
        case 'putData': {
            if (data_exists($_GET['source'])) {
                $status = file_put_contents(realpath($_GET['source']), $datas);
            }
            $status = true;
        }
        break;
        case 'login': {
            if (isset($_SESSION['user']) && $_SESSION['user']['isLogged']) {
                //If user logged, send user data
                $finalData = $_SESSION['user'];
                $status = true;
            } else {
                //Return login form
                $finalData = array(
                    'loginUrl'=> './login.php?ref='.$_SERVER['HTTP_REFERER']
                );
                $status = false;
            }
        }
        break;
        case 'logout': {
            if (isset($_SESSION['user']) && $_SESSION['user']['isLogged']) {
                unset($_SESSION['user']);   
            }
            $status = (!isset($_SESSION['user']));
        }
		break;
        default: {
            $finalData = array('action'=> $_GET['action']);
            $status = false;
        }
        break;
    }
    echo json_encode(array(
       'status'=> $status,
       'data'=> $finalData
    ));
?>
