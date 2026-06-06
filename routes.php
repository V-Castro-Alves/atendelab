<?php
// Carrega o controller responsável pelos endpoints de usuários.
// Observação: o arquivo no projeto está no singular (UsuariosController.php).
require_once __DIR__ . '/app/Controllers/UsuariosController.php';

// Define controller e action por query string.
// Exemplo: ?controller=usuarios&action=listar
$controller = $_GET['controller'] ?? 'home';
$action = $_GET['action'] ?? 'index';

// Este roteador é simples: só reconhece o controller "usuarios".
if ($controller === 'usuarios') {
    $usuariosController = new UsuariosController();

    // Escolhe qual método do controller executar.
    switch ($action) {
        case 'listar':
            $usuariosController->listar();
            break;

        case 'buscar':
            $usuariosController->buscarPorId();
            break;

        case 'criar':
            $usuariosController->criar();
            break;

        case 'atualizar':
            $usuariosController->atualizar();
            break;

        case 'excluir':
            $usuariosController->excluir();
            break;

        default:
            // Retorno padrão para action inválida.
            echo 'Ação de usuários não encontrada.';
            break;
    }
} else {
    // Resposta básica para indicar que a aplicação está no ar.
    echo '<h1>AtendeLab</h1>';
    echo '<p>Projeto em execução. Use ?controller=usuarios&action=listar para testar.</p>';
}