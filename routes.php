<?php

require_once __DIR__ . '/app/Controllers/AuthController.php';
require_once __DIR__ . '/app/Controllers/UsuariosController.php';
require_once __DIR__ . '/app/Controllers/PessoasController.php';
require_once __DIR__ . '/app/Controllers/TiposAtendimentosController.php';
require_once __DIR__ . '/app/Controllers/AtendimentosController.php';
require_once __DIR__ . '/app/Controllers/DashboardController.php';
require_once __DIR__ . '/app/Controllers/RelatoriosController.php';
require_once __DIR__ . '/app/Middleware/auth.php';

$controller = $_GET['controller'] ?? 'auth';
$action = $_GET['action'] ?? 'login';

switch ($controller) {
    case 'frontend':
        switch ($action) {
            case 'pessoas':
                (new PessoasController())->index();
                break;

            case 'tipos':
                (new TiposAtendimentosController())->index();
                break;

            case 'atendimentos':
                (new AtendimentosController())->index();
                break;

            default:
                http_response_code(404);
                echo 'Acao de frontend nao encontrada.';
        }
        break;

    case 'pessoas':
        exigirAutenticacao();
        $pessoasController = new PessoasController();

        switch ($action) {
            case 'index':
                $pessoasController->index();
                break;

            case 'listar':
                $pessoasController->listar();
                break;

            case 'buscar':
                $pessoasController->buscar();
                break;

            case 'criar':
                $pessoasController->criar();
                break;

            case 'atualizar':
                $pessoasController->atualizar();
                break;

            case 'inativar':
                $pessoasController->inativar();
                break;

            default:
                http_response_code(404);
                echo 'Acao de pessoas nao encontrada.';
        }
        break;

    case 'tipos-atendimentos':
    case 'tipos':
        exigirAutenticacao();
        $tiposController = new TiposAtendimentosController();

        switch ($action) {
            case 'index':
                $tiposController->index();
                break;

            case 'listar':
                $tiposController->listar();
                break;

            case 'buscar':
                $tiposController->buscar();
                break;

            case 'criar':
                $tiposController->criar();
                break;

            case 'atualizar':
                $tiposController->atualizar();
                break;

            case 'inativar':
                $tiposController->inativar();
                break;

            default:
                http_response_code(404);
                echo 'Acao de tipos nao encontrada.';
        }
        break;

    case 'atendimentos':
        exigirAutenticacao();
        $atendimentosController = new AtendimentosController();

        switch ($action) {
            case 'index':
                $atendimentosController->index();
                break;

            case 'listar':
                $atendimentosController->listar();
                break;

            case 'buscar':
                $atendimentosController->buscar();
                break;

            case 'criar':
                $atendimentosController->criar();
                break;

            case 'alterarStatus':
            case 'atualizarStatus':
                $atendimentosController->alterarStatus();
                break;

            case 'opcoesFormulario':
                $atendimentosController->opcoesFormulario();
                break;

            default:
                http_response_code(404);
                echo 'Acao de atendimentos nao encontrada.';
        }
        break;

    case 'auth':
        $authController = new AuthController();

        switch ($action) {
            case 'login':
                $authController->exibirLogin();
                break;

            case 'entrar':
                $authController->entrar();
                break;

            case 'dashboard':
                $authController->dashboard();
                break;

            case 'logout':
                $authController->logout();
                break;

            default:
                http_response_code(404);
                echo 'Acao de autenticacao nao encontrada.';
        }
        break;

    case 'usuarios':
        exigirAutenticacao();
        $usuariosController = new UsuariosController();

        switch ($action) {
            case 'listar':
                $usuariosController->listar();
                break;

            case 'buscarPorId':
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
                http_response_code(404);
                echo 'Acao de usuarios nao encontrada.';
        }
        break;

    case 'dashboard':
        exigirAutenticacao();
        $dashboardController = new DashboardController();

        switch ($action) {
            case 'index':
                $dashboardController->index();
                break;

            default:
                http_response_code(404);
                echo 'Acao de dashboard nao encontrada.';
        }
        break;

    case 'relatorios':
        exigirAutenticacao();
        $relatoriosController = new RelatoriosController();

        switch ($action) {
            case 'index':
                $relatoriosController->index();
                break;

            default:
                http_response_code(404);
                echo 'Acao de relatorios nao encontrada.';
        }
        break;

    default:
        http_response_code(404);
        echo 'Controller nao encontrado.';
}
