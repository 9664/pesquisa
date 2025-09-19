<?php
// =====================================================
// admin/api/check-session.php
// =====================================================
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (isset($_SESSION['admin_id'])) {
    echo json_encode(array(
        "success" => true,
        "logged_in" => true,
        "user" => array(
            "id" => $_SESSION['admin_id'],
            "username" => $_SESSION['admin_username'],
            "nome" => $_SESSION['admin_nome']
        )
    ));
} else {
    echo json_encode(array(
        "success" => false,
        "logged_in" => false,
        "message" => "Usuário não logado"
    ));
}
?>