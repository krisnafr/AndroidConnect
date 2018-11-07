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

            $sql = "SELECT token FROM device WHERE nip = " . $nip;
            $res = mysqli_query($this->con, $sql);
            $row = mysqli_fetch_assoc($res);
            $tokens = $row["token"];

            require_once 'push.php';
            require_once 'firebase.php';

            $push = new Push(
                    "Perhatian!", "NIP Anda login di device lain, mohon logout", null
            );

            $mPushNotification = $push->getPush();
            $tokenss = array();
            array_push($tokenss, $tokens);
            $devicetoken = $tokenss;
            $firebase = new Firebase();
            echo $firebase->send($devicetoken, $mPushNotification);

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
        $stmt->bind_param("s", $nip);
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

    //Create Perintah
    function cPerintah($surat, $nip) {
        $stmt = $this->con->prepare("INSERT INTO perintah (id_surat, nip) VALUES (?, ?)");
        $stmt->bind_param("is", $surat, $nip);
        $stmt->execute();
        $stmt->close();
    }

    //Create Laporan
    function cLaporan($perintah, $isi) {
        $stmt = $this->con->prepare("INSERT INTO laporan (no_perintah, isi) VALUES (?, ?)");
        $stmt->bind_param("is", $perintah, $isi);
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
        $stmt = $this->con->prepare("SELECT token FROM device, "
                . "(SELECT nip FROM user WHERE fungsional = ? OR pamong = ? OR program = ? OR sik = ? OR psd = ? OR subbag = ? OR wiyata = ?) AS target "
                . "WHERE device.nip=target.nip");
        $stmt->bind_param("iiiiiii", $a, $b, $c, $d, $e, $f, $g);
        $stmt->execute();
        $result = $stmt->get_result();
        $tokens = array();
        while ($temp = $result->fetch_assoc()) {
            array_push($tokens, $temp['token']);
        }
        $stmt->close();
        return $tokens;
    }

    //Read All Info
    function rInfo() {
        $stmt = $this->con->prepare("SELECT no_info, user.nama, waktu, isi, gambar_info "
                . "FROM info, user "
                . "WHERE info.nip = user.nip");
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
        $stmt = $this->con->prepare("SELECT no_transaksi, user.nama, info.isi, "
                . "info.gambar_info, status, info.waktu "
                . "FROM `transaksi`, `info`, `user` "
                . "WHERE transaksi.no_info = info.no_info AND info.nip = user.nip AND transaksi.nip = ? "
                . "ORDER BY no_transaksi ASC");
        $stmt->bind_param("s", $nip);
        $stmt->execute();
        $stmt->bind_result($no, $nama, $isi, $gambar, $status, $waktu);

        $infos = array();
        while ($stmt->fetch()) {
            $info = array();
            $info['no'] = $no;
            $info['nama'] = $nama;
            $info['isi'] = $isi;
            if (!is_null($gambar)) {
                $info['gambar'] = 'https://' . $this->server_ip . "/gambar/info/" . $gambar;
            } else {
                $info['gambar'] = "";
            }
            $info['status'] = $status;
            $info['waktu'] = $waktu;

            array_push($infos, $info);
        }
        $stmt->close();
        return $infos;
    }

    function rInfoSend($nip) {
        $stmt = $this->con->prepare("SELECT no_info, isi, gambar_info, waktu "
                . "FROM info "
                . "WHERE nip = ? "
                . "ORDER BY no_info ASC");
        $stmt->bind_param("s", $nip);
        $stmt->execute();
        $stmt->bind_result($no, $isi, $gambar, $waktu);

        $info = array();
        while ($stmt->fetch()) {
            $temp = array();
            $temp['no_info'] = $no;
            $temp['isi'] = $isi;
            if (!is_null($gambar)) {
                $temp['gambar'] = 'https://' . $this->server_ip . "/gambar/info/" . $gambar;
            } else {
                $temp['gambar'] = "";
            }
            $temp['waktu'] = $waktu;

            array_push($info, $temp);
        }
        $stmt->close();
        return $info;
    }

    function rInfoStatus($no) {
        $stmt = $this->con->prepare("SELECT no_transaksi, user.nama, status, transaksi.waktu "
                . "FROM transaksi, user "
                . "WHERE transaksi.nip=user.nip AND no_info = ? "
                . "ORDER BY no_transaksi ASC");
        $stmt->bind_param("i", $no);
        $stmt->execute();
        $stmt->bind_result($no, $nama, $status, $waktu);

        $info = array();
        while ($stmt->fetch()) {
            $temp = array();
            $temp['no_transaksi'] = $no;
            $temp['nama'] = $nama;
            $temp['status'] = $status;
            $temp['waktu'] = $waktu;

            array_push($info, $temp);
        }
        $stmt->close();
        return $info;
    }

    //Read All User
    function rUser() {
        $stmt = $this->con->prepare("SELECT nip, password, nama, gambar_user, karyawan, pengawas, admin, "
                . "fungsional, pamong, program, sik, psd, subbag, wiyata "
                . "FROM user");
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
        $stmt = $this->con->prepare("SELECT nip, password, nama, gambar_user, karyawan, pengawas, admin, "
                . "fungsional, pamong, program, sik, psd, subbag, wiyata "
                . "FROM user "
                . "WHERE nip = ?");
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

    //Read Perintah
    function rPerintah($nip) {
        $stmt = $this->con->prepare("SELECT no_perintah, surat_tugas.no_surat, perintah.waktu, "
                . "status, surat_tugas.perihal, surat_tugas.tempat, surat_tugas.waktu, surat_tugas.kategori "
                . "FROM perintah, surat_tugas "
                . "WHERE perintah.id_surat = surat_tugas.id_surat AND nip = ? "
                . "ORDER BY no_perintah ASC");
        $stmt->bind_param("s", $nip);
        $stmt->execute();
        $stmt->bind_result($noPerintah, $noSurat, $waktu, $status, $perihal, $tempat, $durasi, $kategori);

        $perintah = array();

        while ($stmt->fetch()) {
            $temp = array();
            $temp['noPerintah'] = $noPerintah;
            $temp['noSurat'] = $noSurat;
            $temp['kategori'] = $kategori;
            $temp['perihal'] = $perihal;
            $temp['tempat'] = $tempat;
            $temp['durasi'] = $durasi;
            $temp['waktu'] = $waktu;
            $temp['status'] = $status;

            array_push($perintah, $temp);
        }
        $stmt->close();
        return $perintah;
    }

    function rLaporan($nip) {
        $stmt = $this->con->prepare("SELECT no_laporan, no_surat, surat_tugas.perihal, surat_tugas.tempat, "
                . "surat_tugas.waktu, surat_tugas.kategori, laporan.waktu, laporan.isi, "
                . "laporan.gambar_laporan1, laporan.gambar_laporan2, laporan.gambar_laporan3 "
                . "FROM laporan, perintah, surat_tugas "
                . "WHERE laporan.no_perintah = perintah.no_perintah AND perintah.id_surat = surat_tugas.id_surat "
                . "AND perintah.nip = ? "
                . "ORDER BY no_laporan ASC");
        $stmt->bind_param("s", $nip);
        $stmt->execute();
        $stmt->bind_result($noLap, $noSurat, $perihal, $tempat, $durasi, $kategori, $waktu, $isi, $pic1, $pic2, $pic3);

        $laporan = array();

        while ($stmt->fetch()) {
            $temp = array();
            $temp['noLaporan'] = $noLap;
            $temp['noSurat'] = $noSurat;
            $temp['kategori'] = $kategori;
            $temp['perihal'] = $perihal;
            $temp['tempat'] = $tempat;
            $temp['durasi'] = $durasi;
            $temp['waktu'] = $waktu;
            $temp['isi'] = $isi;
            if (!is_null($pic1)) {
                $temp['pic1'] = 'https://' . $this->server_ip . "/gambar/laporan/" . $pic1;
            } else {
                $temp['pic1'] = "";
            }
            if (!is_null($pic2)) {
                $temp['pic2'] = 'https://' . $this->server_ip . "/gambar/laporan/" . $pic2;
            } else {
                $temp['pic2'] = "";
            }
            if (!is_null($pic3)) {
                $temp['pic3'] = 'https://' . $this->server_ip . "/gambar/laporan/" . $pic3;
            } else {
                $temp['pic3'] = "";
            }

            array_push($laporan, $temp);
        }
        $stmt->close();
        return $laporan;
    }

    function rAtasan($nip) {
        $stmt = $this->con->prepare("SELECT nip, fungsional, pamong, program, sik, psd, subbag, wiyata "
                . "FROM user "
                . "WHERE nip = " . $nip);
        $stmt->execute();
        $stmt->bind_result($nip, $a, $b, $c, $d, $e, $f, $g);
        $stmt->fetch();
        $stmt->close();
        return $this->rLaporanKaryawan($nip, $a, $b, $c, $d, $e, $f, $g);
    }

    function rLaporanKaryawan($nip, $a, $b, $c, $d, $e, $f, $g) {
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
        $stmt = $this->con->prepare("SELECT target.nama, no_laporan, no_surat, surat_tugas.perihal, "
                . "surat_tugas.tempat, surat_tugas.waktu, surat_tugas.kategori, laporan.waktu, laporan.isi, "
                . "laporan.gambar_laporan1, laporan.gambar_laporan2, laporan.gambar_laporan3 "
                . "FROM laporan, perintah, surat_tugas, "
                . "(SELECT nip, nama FROM user WHERE fungsional = ? OR pamong = ? OR program = ? OR sik = ? "
                . "OR psd = ? OR subbag = ? OR wiyata = ?) AS target WHERE laporan.no_perintah = perintah.no_perintah "
                . "AND perintah.id_surat = surat_tugas.id_surat AND target.nip = perintah.nip AND NOT target.nip = ?");
        $stmt->bind_param("iiiiiiis", $a, $b, $c, $d, $e, $f, $g, $nip);
        $stmt->execute();
        $stmt->bind_result($nama, $noLap, $noSurat, $perihal, $tempat, $durasi, $kategori, $waktu, $isi, $pic1, $pic2, $pic3);

        $laporanK = array();

        while ($stmt->fetch()) {
            $temp = array();
            $temp['nama'] = $nama;
            $temp['noLaporan'] = $noLap;
            $temp['noSurat'] = $noSurat;
            $temp['kategori'] = $kategori;
            $temp['perihal'] = $perihal;
            $temp['tempat'] = $tempat;
            $temp['durasi'] = $durasi;
            $temp['waktu'] = $waktu;
            $temp['isi'] = $isi;
            if (!is_null($pic1)) {
                $temp['pic1'] = 'https://' . $this->server_ip . "/gambar/laporan/" . $pic1;
            } else {
                $temp['pic1'] = "";
            }
            if (!is_null($pic2)) {
                $temp['pic2'] = 'https://' . $this->server_ip . "/gambar/laporan/" . $pic2;
            } else {
                $temp['pic2'] = "";
            }
            if (!is_null($pic3)) {
                $temp['pic3'] = 'https://' . $this->server_ip . "/gambar/laporan/" . $pic3;
            } else {
                $temp['pic3'] = "";
            }

            array_push($laporanK, $temp);
        }
        $stmt->close();
        return $laporanK;
    }

    /*
     * The update operation
     * When this method is called the record with the given id is updated with the new given values
     */

    function uStatus($no, $status) {
        $stmt = $this->con->prepare("UPDATE transaksi SET status = ? WHERE no_transaksi = " . $no);
        $stmt->bind_param("i", $status);
        $stmt->execute();
        $stmt->close();
    }

    function uPassword($nip, $pass) {
        $passw = hash("sha256", $pass);
        $stmt = $this->con->prepare("UPDATE user SET user.password = ? WHERE nip = ?");
        $stmt->bind_param("ss", $passw, $nip);
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

    function dInfo($no) {
        $stmt = $this->con->prepare("DELETE FROM transaksi WHERE no_transaksi = ? ");
        $stmt->bind_param("i", $no);
        $stmt->execute();
        $stmt->close();
    }

    function dInfos($no) {
        $stmt = $this->con->prepare("DELETE FROM transaksi WHERE no_info = ? ");
        $stmt->bind_param("i", $no);
        $stmt->execute();
        $stmt->close();
        $stmt = $this->con->prepare("DELETE FROM info WHERE no_info = ? ");
        $stmt->bind_param("i", $no);
        $stmt->execute();
        $stmt->close();
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
