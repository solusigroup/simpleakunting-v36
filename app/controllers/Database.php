<?php

class Database extends Controller {
    public function __construct() {
        parent::__construct();
        if (!Auth::isLoggedIn() || (!Auth::isAdmin() && !Auth::isActuallySuperadmin())) {
            Flash::setFlash('Akses Ditolak', 'Hanya Administrator yang dapat melakukan backup database.', 'danger');
            header('Location: ' . BASEURL . '/dashboard');
            exit;
        }
    }

    public function backup() {
        // Nama file backup
        $filename = 'backup_bumdesa_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Header untuk download file
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Ambil semua tabel
        // Gunakan FETCH_COLUMN agar mendapatkan array daftar nama tabel
        $query = $this->db->dbh->query("SHOW TABLES");
        $tables = $query->fetchAll(PDO::FETCH_COLUMN);

        echo "-- SimpleAkunting v3.6 Database Backup\n";
        echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        echo "-- BUMDesa Digital Platform\n\n";
        echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $tableName) {
            // 1. SHOW CREATE TABLE
            $queryCreate = $this->db->dbh->query("SHOW CREATE TABLE `$tableName` ");
            $createTable = $queryCreate->fetch(PDO::FETCH_ASSOC);
            echo "-- Structure for table `$tableName` --\n";
            echo "DROP TABLE IF EXISTS `$tableName`;\n";
            echo $createTable['Create Table'] . ";\n\n";

            // 2. Data
            $queryData = $this->db->dbh->query("SELECT * FROM `$tableName` ");
            $rows = $queryData->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($rows) > 0) {
                echo "-- Dumping data for table `$tableName` --\n";
                foreach ($rows as $row) {
                    $keys = array_keys($row);
                    $values = array_values($row);
                    
                    $valArr = [];
                    foreach ($values as $val) {
                        if (is_null($val)) {
                            $valArr[] = "NULL";
                        } else {
                            $valArr[] = "'" . addslashes($val) . "'";
                        }
                    }
                    
                    echo "INSERT INTO `$tableName` (`" . implode("`, `", $keys) . "`) VALUES (" . implode(", ", $valArr) . ");\n";
                }
                echo "\n";
            }
        }

        echo "SET FOREIGN_KEY_CHECKS=1;\n";
        exit;
    }
}
