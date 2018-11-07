<?php

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

        //the REGISTER DEVICE
        case 'c_device':
            isTheseParametersAvailable(array('nip', 'token'));
            $nip = $_POST['nip'];
            $token = $_POST['token'];

            $db = new DbOperation();

            $result = $db->regDevice($nip, $token);

            if ($result == 0) {
                $response['error'] = false;
                $response['message'] = 'Device registered successfully';
            }
            if ($result == 1) {
                $response['error'] = true;
                $response['message'] = 'Device already registered';
            }
            break;

        //the CREATE operation
        case 'c_info':
            //first check the parameters required for this request are available or not 
            isTheseParametersAvailable(array('nip', 'isi'));

            //creating a new dboperation object
            $db = new DbOperation();

            //creating a new record in the database
            $result = $db->cInfo(
                    $_POST['nip'], $_POST['isi']
            );

            require_once '../include/dbConnect.php';
            $dbc = new DbConnect();
            $con = $dbc->connect();
            $path = $base_path_pic . 'info/';
            $year_folder = $path . date('Y');
            $month_folder = $year_folder . '/' . date('n');
            $path = $month_folder . '/';

            !file_exists($year_folder) && mkdir($year_folder, 0777);
            !file_exists($month_folder) && mkdir($month_folder, 0777);
            define('UPLOAD_PATH', $path);

            $sql = "SELECT no_info FROM info ORDER BY no_info ASC";
            $res = mysqli_query($con, $sql);
            while ($row = mysqli_fetch_array($res)) {
                $no = $row['no_info'];
            }
            if (isset($_FILES['pic']['name'])) {
                try {
                    $imageName = $no . ".png";
                    move_uploaded_file($_FILES['pic']['tmp_name'], UPLOAD_PATH . $imageName);
                    $paths = date('Y') . '/' . date('n') . '/' . $imageName;
                    $stmt = $con->prepare("UPDATE info SET gambar_info = ? WHERE no_info = " . $no);
                    $stmt->bind_param("s", $paths);
                    if ($stmt->execute()) {
                        $response['error'] = false;
                        $response['message'] = 'File uploaded successfully';
                    } else {
                        throw new Exception("Could not upload file");
                    }
                } catch (Exception $e) {
                    $response['error'] = true;
                    $response['message'] = 'Could not upload file';
                }
            } else {
                $response['error'] = true;
                $response['message'] = "Required params not available";
            }

            isTheseParametersAvailable(array('a', 'b', 'c', 'd', 'e', 'f', 'g'));
            $response['error'] = false;
            $response['message'] = 'Request successfully completed';
            $response['tokens'] = $db->rTargetUser(
                    $no, $_POST['a'], $_POST['b'], $_POST['c'], $_POST['d'], $_POST['e'], $_POST['f'], $_POST['g']
            );

            require_once 'push.php';
            require_once 'firebase.php';

            $sql = "SELECT nama FROM user WHERE nip = " . $_POST['nip'];
            $res = mysqli_query($con, $sql);
            $row = mysqli_fetch_assoc($res);
            $nama = $row["nama"];

            //creating a new push
            $push = null;
            //first check if the push has an image with it
            if (isset($_FILES['pic']['name'])) {
                $push = new Push(
                        "Informasi Baru!", $nama, $_FILES['pic']['name']
                );
            } else {
                //if the push don't have an image give null in place of image
                $push = new Push(
                        "Informasi Baru!", $nama, null
                );
            }

            //getting the push from push object
            $mPushNotification = $push->getPush();

            //getting the token from database object 
            $devicetoken = $response['tokens'];

            //creating firebase class object 
            $firebase = new Firebase();

            //sending push notification and displaying result 
            echo $firebase->send($devicetoken, $mPushNotification);

            break;

        case 'c_user':
            //first check the parameters required for this request are available or not 
            isTheseParametersAvailable(array('nip', 'password', 'nama', 'karyawan', 'pengawas', 'admin', 'fungsional', 'pamong', 'program', 'sik', 'psd', 'subbag', 'wiyata'));

            //creating a new dboperation object
            $db = new DbOperation();

            //creating a new record in the database
            $result = $db->createUser(
                    $_POST['nip'], $_POST['password'], $_POST['nama'], $_POST['karyawan'], $_POST['pengawas'], $_POST['admin'], $_POST['fungsional'], $_POST['pamong'], $_POST['program'], $_POST['sik'], $_POST['psd'], $_POST['subbag'], $_POST['wiyata']
            );


            //if the record is created adding success to response
            if ($result) {
                //record is created means there is no error
                $response['error'] = false;
                //in message we have a success message
                $response['message'] = 'User berhasil ditambahkan';
                //and we are getting all the heroes from the database in the response
                $response['msgs'] = $db->getUser();
            } else {
                //if record is not added that means there is an error 
                $response['error'] = true;
                //and we have the error message
                $response['message'] = 'Terjadi kesalahan';
            }

            break;

        case 'c_laporan':
            //first check the parameters required for this request are available or not 
            isTheseParametersAvailable(array('nop', 'isi'));

            //creating a new dboperation object
            $db = new DbOperation();

            //creating a new record in the database
            $result = $db->cLaporan(
                    $_POST['nop'], $_POST['isi']
            );

            $response['nop'] = 'id perintah: ' . $_POST['nop'];
            $response['isi'] = 'isi laporan' . $_POST['isi'];

            require_once '../include/dbConnect.php';
            $dbc = new DbConnect();
            $con = $dbc->connect();
            $path = $base_path_pic . 'laporan/';
            $year_folder = $path . date('Y');
            $month_folder = $year_folder . '/' . date('n');
            $path = $month_folder . '/';

            !file_exists($year_folder) && mkdir($year_folder, 0777);
            !file_exists($month_folder) && mkdir($month_folder, 0777);
            define('UPLOAD_PATH', $path);

            $sql = "SELECT no_laporan FROM laporan ORDER BY no_laporan ASC";
            $res = mysqli_query($con, $sql);
            while ($row = mysqli_fetch_array($res)) {
                $no = $row['no_laporan'];
            }

            if (isset($_FILES['pic1']['name'])) {
                try {
                    $imageName = $no . "_1.png";
                    move_uploaded_file($_FILES['pic1']['tmp_name'], UPLOAD_PATH . $imageName);
                    $paths = date('Y') . '/' . date('n') . '/' . $imageName;
                    $stmt = $con->prepare("UPDATE laporan SET gambar_laporan1 = ? WHERE no_laporan = " . $no);
                    $stmt->bind_param("s", $paths);
                    $stmt->execute();
                    $stmt->close();
                } catch (Exception $e) {
                    $response['error'] = true;
                    $response['message'] = 'Could not upload file';
                }
            }

            if (isset($_FILES['pic2']['name'])) {
                try {
                    $imageName = $no . "_2.png";
                    move_uploaded_file($_FILES['pic2']['tmp_name'], UPLOAD_PATH . $imageName);
                    $paths = date('Y') . '/' . date('n') . '/' . $imageName;
                    $stmt = $con->prepare("UPDATE laporan SET gambar_laporan2 = ? WHERE no_laporan = " . $no);
                    $stmt->bind_param("s", $paths);
                    $stmt->execute();
                    $stmt->close();
                } catch (Exception $e) {
                    $response['error'] = true;
                    $response['message'] = 'Could not upload file';
                }
            }

            if (isset($_FILES['pic3']['name'])) {
                try {
                    $imageName = $no . "_3.png";
                    move_uploaded_file($_FILES['pic3']['tmp_name'], UPLOAD_PATH . $imageName);
                    $paths = date('Y') . '/' . date('n') . '/' . $imageName;
                    $stmt = $con->prepare("UPDATE laporan SET gambar_laporan3 = ? WHERE no_laporan = " . $no);
                    $stmt->bind_param("s", $paths);
                    $stmt->execute();
                    $stmt->close();
                } catch (Exception $e) {
                    $response['error'] = true;
                    $response['message'] = 'Could not upload file';
                }
            }

            break;

//the UPDATE operation
        case 'u_status':
            if (isset($_GET['no'])) {
                isTheseParametersAvailable(array('status'));
                $db = new DbOperation();
                $response['error'] = false;
                $response['message'] = 'Request successfully completed';
                $db->uStatus(
                        $_GET['no'], $_POST['status']
                );
            }
            break;

        case 'u_pass':
            if (isset($_GET['nip'])) {
                isTheseParametersAvailable(array('pass'));
                $db = new DbOperation();
                $response['error'] = false;
                $response['message'] = 'Request successfully completed';
                $db->uPassword(
                        $_GET['nip'], $_POST['pass']
                );
            }
            break;

        case 'updatemsg':
            isTheseParametersAvailable(array('no', 'isi'));
            $db = new DbOperation();
            $result = $db->updateMsg(
                    $_POST['no'], $_POST['isi']
            );

            if ($result) {
                $response['error'] = false;
                $response['message'] = 'Message updated successfully';
                $response['msgs'] = $db->getMsg();
            } else {
                $response['error'] = true;
                $response['message'] = 'Some error occurred please try again';
            }
            break;


        case 'updateuser':
            isTheseParametersAvailable(array('nip', 'nama'));
            $db = new DbOperation();
            $result = $db->updateUser(
                    $_POST['nip'], $_POST['nama']
            );

            if ($result) {
                $response['error'] = false;
                $response['message'] = 'Message updated successfully';
                $response['msgs'] = $db->getUser();
            } else {
                $response['error'] = true;
                $response['message'] = 'Some error occurred please try again';
            }
            break;


//the Delete operation
        case 'd_info':
            if (isset($_GET['no'])) {
                $db = new DbOperation();
                $db->dInfo($_GET['no']);
                $response['no_transaksi'] = 'no transaksi: '.$_GET['no'];
            }
            break;

        case 'd_infos':
            if (isset($_GET['no'])) {
                $db = new DbOperation();
                $db->dInfos($_GET['no']);
                $response['no_info'] = 'no info: '.$_GET['no'];
            }
            break;

        case 'deleteuser':

            //for the delete operation we are getting a GET parameter from the url having the id of the record to be deleted
            if (isset($_GET['nip'])) {
                $db = new DbOperation();
                if ($db->deleteUser($_GET['nip'])) {
                    $response['error'] = false;
                    $response['message'] = 'User deleted successfully';
                    $response['msgs'] = $db->getUser();
                } else {
                    $response['error'] = true;
                    $response['message'] = 'Some error occurred please try again';
                }
            } else {
                $response['error'] = true;
                $response['message'] = 'Nothing to delete, provide an id please';
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
