<?php
require_once 'app/config.php';
require_once 'app/core/Database.php';

$db = new Database();
$pass = password_hash('admin123', PASSWORD_DEFAULT);

$db->query("UPDATE users SET password_hash = :pass WHERE nama_user = 'superadmin'");
$db->bind('pass', $pass);
$db->execute();

if ($db->rowCount() > 0) {
    echo "✅ Superadmin password has been reset to: admin123\n";
} else {
    // Maybe user doesn't exist? Try insert
    $db->query("INSERT INTO users (nama_user, password_hash, role, role_id, jabatan, tenant_id) VALUES ('superadmin', :pass, 'Superadmin', 1, 'CEO', 1)");
    $db->bind('pass', $pass);
    $db->execute();
    echo "✅ Superadmin user was missing and has been created with password: admin123\n";
}
