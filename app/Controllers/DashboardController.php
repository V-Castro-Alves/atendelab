<?php
// Controller para o painel de controle (Dashboard).
class DashboardController
{
    public function index(): void
    {
        // Bloqueia o acesso caso o usuário não esteja logado.
        exigirAutenticacao();

        // Recupera os dados do usuário autenticado.
        $usuario = usuarioAtual();

        // Carrega a página interna.
        require __DIR__ . '/../Views/dashboard/index.php';
    }
}
