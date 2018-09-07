<?php

class DbOperation {

    //Database connection link
    private $con;
    private $server_ip = "cilukbaa.000webhostapp.com";

    //Class constructor
    function __construct() {
        //Getting the DbConnect.php file
        require_once dirname(__FILE__) . '/dbConnect.php';

        //Creating a DbConnect object to connect to the database
        $db = new DbConnect();

        //Initializing our connection link of this class
        //by calling the method connect of DbConnect class
        $this->con = $db->connect();
    }

    //Register Device
    function regDevice($nip, $token) {
        if ($this->isNipExist($nip)) {
            $stmt = $this->con->prepare("UPDATE device SET token = ? WHERE nip = ?");
            $stmt->bind_param("ss", $token, $nip);
        } else {
            $stmt = $this->con->prepare("INSERT INTO device (nip, token) VALUES (?,?) ");
            $stmt->bind_param("ss", $nip, $token);
        }
        $stmt->execute();
        $stmt->close();
    }

    function isNipExist($nip) {
        $stmt = $this->con->prepare("SELECT no_device FROM device WHERE nip = ?");
        $stmt->bind_param("s",$nip);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /*
     * The create operation
     * When this method is called a new record is created in the database
     */

    //Create Info
    function cInfo($nip, $isi) {
        $stmt = $this->con->prepare("INSERT INTO info (nip, isi) VALUES (?, ?)");
        $stmt->bind_param("ss", $nip, $isi);
        $stmt->execute();
        $stmt->close();
    }

    //Create User
    function cUser($nip, $password, $nama, $gambar_user, $karyawan, $pengawas, $admin, $fungsional, $pamong, $program, $sik, $psd, $subbag, $wiyata) {
        $stmt = $this->con->prepare("INSERT INTO user "
                . "(nip, password, nama, gambar_user, karyawan, pengawas, admin, fungsional, pamong, program, sik, psd, subbag, wiyata) "
                . "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssiiiiiiiiii", $nip, $password, $nama, $gambar_user, $karyawan, $pengawas, $admin, $fungsional, $pamong, $program, $sik, $psd, $subbag, $wiyata);
        $stmt->execute();
        $stmt->close();
    }

    /*
     * The READ operation
     * When this method is called it is returning all the existing record of the database
     */

    //Read Info
    //Read Recipient
    function rTargetUser($no, $a, $b, $c, $d, $e, $f, $g) {
        if ($a == 0) {
            $a = null;
        }
        if ($b == 0) {
            $b = null;
        }
        if ($c == 0) {
            $c = null;
        }
        if ($d == 0) {
            $d = null;
        }
        if ($e == 0) {
            $e = null;
        }
        if ($f == 0) {
            $f = null;
        }
        if ($g == 0) {
            $g = null;
        }
        $stmt = $this->con->prepare("INSERT INTO transaksi (nip, no_info)"
                . "SELECT nip," . $no . " FROM user WHERE fungsional = ? OR pamong = ? OR program = ? OR sik = ? OR psd = ? OR subbag = ? OR wiyata = ?");
        $stmt->bind_param("iiiiiii", $a, $b, $c, $d, $e, $f, $g);
        $stmt->execute();
        $stmt->close();
        //Ambil semua token dari nip target
        $stmt = $this->con->prepare("SELECT token FROM device, (SELECT nip FROM user WHERE fungsional = ? OR pamong = ? OR program = ? OR sik = ? OR psd = ? OR subbag = ? OR wiyata = ?) AS target "
                . "WHERE device.nip=target.nip");
        $stmt->bind_param("iiiiiii", $a, $b, $c, $d, $e, $f, $g);
        $stmt->execute();
        $result = $stmt->get_result();
        $tokens = array();
        while ($token = $result->fetch_assoc()) {
            array_push($tokens, $token['token']);
        }
        $stmt->close();
        return $tokens;
    }

    //Read All Info
    function rInfo() {
        $stmt = $this->con->prepare("SELECT no_info, user.nama, waktu, isi, gambar_info FROM info JOIN user ON info.nip = user.nip");
        $stmt->execute();
        $stmt->bind_result($no_info, $nama, $waktu, $isi, $gambar);

        $infos = array();

        while ($stmt->fetch()) {
            $info = array();
            $info['no_info'] = $no_info;
            $info['nama'] = $nama;
            $info['waktu'] = $waktu;
            $info['isi'] = $isi;
            $info['gambar'] = $gambar;

            array_push($infos, $info);
        }
        $stmt->close();
        return $infos;
    }

    function rInfoNip($nip) {
        $stmt = $this->con->prepare("SELECT no_transaksi, user.nama, info.isi, info.gambar_info, status, info.waktu "
                . "FROM `transaksi`, `info`, `user` "
                . "WHERE transaksi.no_info = info.no_info AND info.nip = user.nip AND transaksi.nip = ?");
        $stmt->bind_param("s", $nip);
        $stmt->execute();
        $stmt->bind_result($no, $nama, $isi, $gambar, $status, $waktu);

        $infos = array();
        while ($stmt->fetch()) {
            $info = array();
            $info['no'] = $no;
            $info['nama'] = $nama;
            $info['isi'] = $isi;
            $info['gambar'] = 'https://' . $this->server_ip . "/gambar/info/" . $gambar;
            $info['status'] = $status;
            $info['waktu'] = $waktu;

            array_push($infos, $info);
        }
        $stmt->close();
        return $infos;
    }

    function rInfoSend($nip) {
        $stmt = $this->con->prepare("SELECT no_info, isi, gambar_info, waktu FROM info WHERE nip = ?");
        $stmt->bind_param("s", $nip);
        $stmt->execute();
        $stmt->bind_result($no, $isi, $gambar, $waktu);

        $info = array();
        while ($stmt->fetch()) {
            $temp = array();
            $temp["no_info"] = $no;
            $temp["isi"] = $isi;
            $temp["gambar"] = 'https://' . $this->server_ip . "/gambar/info/" . $gambar;
            $temp["waktu"] = $waktu;

            array_push($info, $temp);
        }
        $stmt->close();
        return $info;
    }

    function rInfoStatus($no) {
        $stmt = $this->con->prepare("SELECT no_transaksi, user.nama, status, transaksi.waktu "
                . "FROM transaksi, user "
                . "WHERE transaksi.nip=user.nip AND no_info = ?");
        $stmt->bind_param("i", $no);
        $stmt->execute();
        $stmt->bind_result($no, $nama, $status, $waktu);

        $info = array();
        while ($stmt->fetch()) {
            $temp = array();
            $temp["no_transaksi"] = $no;
            $temp["nama"] = $nama;
            $temp["status"] = $status;
            $temp["waktu"] = $waktu;

            array_push($info, $temp);
        }
        $stmt->close();
        return $info;
    }

    //Read All User
    function rUser() {
        $stmt = $this->con->prepare("SELECT nip, password, nama, gambar_user, karyawan, pengawas, admin, fungsional, pamong, program, sik, psd, subbag, wiyata FROM user");
        $stmt->execute();
        $stmt->bind_result($nip, $password, $nama, $gambar_user, $karyawan, $pengawas, $admin, $fungsional, $pamong, $program, $sik, $psd, $subbag, $wiyata);

        $users = array();

        while ($stmt->fetch()) {
            $user = array();
            $user['nip'] = $nip;
            $user['password'] = $password;
            $user['nama'] = $nama;
            $user['gambar'] = $gambar_user;
            $user['karyawan'] = $karyawan;
            $user['pengawas'] = $pengawas;
            $user['admin'] = $admin;
            $user['fungsional'] = $fungsional;
            $user['pamong'] = $pamong;
            $user['program'] = $program;
            $user['sik'] = $sik;
            $user['psd'] = $psd;
            $user['subbag'] = $subbag;
            $user['wiyata'] = $wiyata;

            array_push($users, $user);
        }
        $stmt->close();
        return $users;
    }

    //Read User Nip
    function rUserNip($nip) {
        $stmt = $this->con->prepare("SELECT nip, password, nama, gambar_user, karyawan, pengawas, admin, fungsional, pamong, program, sik, psd, subbag, wiyata FROM user WHERE nip = ?");
        $stmt->bind_param("s", $nip);
        $stmt->execute();
        $stmt->bind_result($nip, $password, $nama, $gambar_user, $karyawan, $pengawas, $admin, $fungsional, $pamong, $program, $sik, $psd, $subbag, $wiyata);

        $users = array();

        while ($stmt->fetch()) {
            $user = array();
            $user['nip'] = $nip;
            $user['password'] = $password;
            $user['nama'] = $nama;
            $user['gambar'] = $gambar_user;
            $user['karyawan'] = $karyawan;
            $user['pengawas'] = $pengawas;
            $user['admin'] = $admin;
            $user['fungsional'] = $fungsional;
            $user['pamong'] = $pamong;
            $user['program'] = $program;
            $user['sik'] = $sik;
            $user['psd'] = $psd;
            $user['subbag'] = $subbag;
            $user['wiyata'] = $wiyata;

            array_push($users, $user);
        }
        $stmt->close();
        return $users;
    }

    /*
     * The update operation
     * When this method is called the record with the given id is updated with the new given values
     */

    function u_status($no, $status) {
        $stmt = $this->con->prepare("UPDATE transaksi SET status = ? WHERE no_transaksi = " . $no);
        $stmt->bind_param("i", $status);
        $stmt->execute();
        $stmt->close();
    }

    function updateMsg($no, $isi) {
        $stmt = $this->con->prepare("UPDATE pesan SET isi = ? WHERE no = ?");
        $stmt->bind_param("si", $isi, $no);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    function updateUser($nip, $nama) {
        $stmt = $this->con->prepare("UPDATE user SET nama = ? WHERE nip = ?");
        $stmt->bind_param("ss", $nama, $nip);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /*
     * The delete operation
     * When this method is called record is deleted for the given id 
     */

    function deleteMsg($no) {
        $stmt = $this->con->prepare("DELETE FROM pesan WHERE no = ? ");
        $stmt->bind_param("i", $no);
        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    function deleteUser($nip) {
        $stmt = $this->con->prepare("DELETE FROM user WHERE nip = ? ");
        $stmt->bind_param("s", $nip);
        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

}
