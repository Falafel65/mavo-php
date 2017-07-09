<?php
    @session_start();
    $datas = file_get_contents('php://input');
    $status = false;
    $finalData = array();
    
    //Function to know if local file exists, or if can be created
    function data_exists ($id = '') {
        if ($id === '') {
            return false;
        }
        
        $file = $id.'.json';
        if (file_exists($file)) {
            return is_writable($file);
        } else {
            return touch($file);
        }
    }
    
    //Defaults _GET
    if (!isset($_GET['id'])) {
        $_GET['id'] = '';
    }
    if (!isset($_GET['action'])) {
        $_GET['action'] = 'login';
    }
    
    switch ($_GET['action']) {
        case 'putFile': {
            if (data_exists($_GET['id'])) {
                //Upload a file
                if (isset($_GET['file']) && !empty($_GET['file'])) {
                    //We got a filename
                    $filename = $_GET['file'];
                } else {
                    //We have to make a random name
                    $filename = uniqid();
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
            if (data_exists($_GET['id'])) {
                //Get & decode saved datas
                $file = file_get_contents($_GET['id'].'.json');
                $fileData = json_decode($file, true);
                //Decode sent datas
                $sentData = json_decode($datas, true);
                //Merge them
                $finalData = array_merge($fileData, $sentData);
                //Save it
                //JSON_PRETTY_PRINT to be more readable by humans after.
                $status = file_put_contents($_GET['id'].'.json', json_encode($finalData, JSON_PRETTY_PRINT));
            }
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
        default: {
            $finalData = array('action'=> $_GET['action']);
            $status = false;
        }
        break;
    }
    if (isset($_GET['id']) && file_exists($_GET['id'].'.json')) {
        if (isset($_GET['isEncoded']) && $_GET['isEncoded'] == true) {
            
        } else {
            
        }
    } elseif (isset($_GET['action']))
    echo json_encode(array(
       'status'=> $status,
       'data'=> $finalData
    ));
?>
