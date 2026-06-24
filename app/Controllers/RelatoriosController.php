<?php
// Controller para geração de relatórios.
class RelatoriosController
{
    public function index(): void
    {
        // Define saída em JSON.
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'mensagem' => 'Módulo de relatórios e impressão será aprofundado nesta etapa.'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
