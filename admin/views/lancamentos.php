<?php
if (!defined('ABSPATH')) {
    exit;
}

$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'listar';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$tipo = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';

switch ($action) {
    case 'criar':
        include 'lancamentos/criar.php';
        break;
    case 'ver':
        include 'lancamentos/ver.php';
        break;
    case 'editar':
        include 'lancamentos/editar.php';
        break;
    case 'buscar':
        include 'lancamentos/buscar.php';
        break;
    default:
        include 'lancamentos/listar.php';
        break;
}
?>