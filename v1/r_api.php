<?php

header("Cache-Control: max-age=604800, must-revalidate");
//getting the dboperation class
require_once '../include/dbOperation.php';

//function validating all the paramters are available
//we will pass the required parameters to this function 
function isTheseParametersAvailable($params) {
    //assuming all parameters are available 
    $available = true;
    $missingparams = "";

    foreach ($params as $param) {
        if (!isset($_POST[$param]) || strlen($_POST[$param]) <= 0) {
            $available = false;
            $missingparams = $missingparams . ", " . $param;
        }
    }

    //if parameters are missing 
    if (!$available) {
        $response = array();
        $response['error'] = true;
        $response['message'] = 'Parameters ' .
                substr($missingparams, 1, strlen($missingparams)) . ' missing';

        //displaying error
        echo json_encode($response);

        //stopping further execution
        die();
    }
}

//an array to display response
$response = array();

//if it is an api call 
//that means a get parameter named api call is set in the URL 
//and with this parameter we are concluding that it is an api call

if (isset($_GET['apicall'])) {
    $base_path_pic = '../gambar/';
    switch ($_GET['apicall']) {

//the READ operation
        case 'r_info':
            $db = new DbOperation();
            $response['error'] = false;
            $response['message'] = 'Request successfully completed';
            $response['infos'] = $db->rInfo();
            break;

        case 'r_info_nip':
            if (isset($_GET['nip'])) {
                $db = new DbOperation();
                $response['error'] = false;
                $response['message'] = 'Request successfully completed';
                $response['info'] = $db->rInfoNip(
                        $_GET['nip']
                );
            }
            break;

        case 'r_user':
            $db = new DbOperation();
            $response['error'] = false;
            $response['message'] = 'Request successfully completed';
            $response['users'] = $db->rUser();
            break;

        case 'r_user_nip':
            if (isset($_GET['nip'])) {
                $db = new DbOperation();
                $response['error'] = false;
                $response['message'] = 'Request successfully completed';
                $response['users'] = $db->rUserNip(
                        $_GET['nip']
                );
            }
            break;

        case 'r_sent_all':
            if (isset($_GET['nip'])) {
                $db = new DbOperation();
                $response['error'] = false;
                $response['message'] = 'Request successfully completed';
                $response['info'] = $db->rInfoSend(
                        $_GET['nip']
                );
            }
            break;

        case 'r_sent':
            if (isset($_GET['no'])) {
                $db = new DbOperation();
                $response['error'] = false;
                $response['message'] = 'Request successfully completed';
                $response['status'] = $db->rInfoStatus(
                        $_GET['no']
                );
            }
            break;

        case 'r_surat':
            if (isset($_GET['nip'])) {
                $db = new DbOperation();
                $response['error'] = false;
                $response['message'] = 'Request successfully completed';
                $response['surat'] = $db->rPerintah(
                        $_GET['nip']
                );
            }
            break;

        case 'r_laporan':
            if (isset($_GET['nip'])) {
                $db = new DbOperation();
                $response['error'] = false;
                $response['message'] = 'Request successfully completed';
                $response['laporan'] = $db->rLaporan(
                        $_GET['nip']
                );
            }
            break;

        case 'r_laporan_lain':
            if (isset($_GET['nip'])) {
                $db = new DbOperation();
                $response['error'] = false;
                $response['message'] = 'Request successfully completed';
                $response['laporanLain'] = $db->rAtasan(
                        $_GET['nip']
                );
            }
            break;
    }
} else {
//if it is not api call 
//pushing appropriate values to response array 
    $response['error'] = true;
    $response['message'] = 'Invalid API Call';
}

//displaying the response in json structure 
echo json_encode($response);
