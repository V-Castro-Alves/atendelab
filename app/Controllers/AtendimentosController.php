<?php
// Controller da entidade de atendimentos.
// Em uma arquitetura MVC, ele recebe a requisição, valida dados e acessa o banco.
class AtendimentosController
{
    // Conexão PDO reutilizada em todos os métodos.
    private PDO $pdo;

    public function __construct()
    {
        // Importa o arquivo que inicializa o objeto $pdo.
        require __DIR__ . '/../../config/database.php';
        $this->pdo = $pdo;
    }

    public function index(): void
    {
        exigirAutenticacao();

        $usuario = usuarioAtual();
        $controllerAtual = 'atendimentos';
        $tituloPagina = 'Atendimentos';

        require __DIR__ . '/../Views/atendimentos/index.php';
    }

    public function listar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // Consulta com JOIN para trazer dados das tabelas relacionadas.
        $sql = 'SELECT 
                    a.id,
                    a.usuario_id,
                    u.nome AS usuario_nome,
                    a.pessoa_id,
                    p.nome AS pessoa_nome,
                    a.tipo_atendimento_id,
                    t.nome AS tipo_atendimento_nome,
                    a.descricao,
                    a.data_atendimento,
                    a.status,
                    a.horario_atendimento,
                    a.observacao_final,
                    a.criado_em
                FROM atendimentos a
                INNER JOIN usuarios u ON a.usuario_id = u.id
                INNER JOIN pessoas p ON a.pessoa_id = p.id
                INNER JOIN tipos_atendimentos t ON a.tipo_atendimento_id = t.id
                ORDER BY a.id DESC';

        try {
            $stmt = $this->pdo->query($sql);
            $atendimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($atendimentos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao listar atendimentos.']);
        }
    }

    public function buscar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['erro' => 'ID inválido.']);
            return;
        }

        // Consulta parametrizada com JOIN para buscar detalhes de um atendimento específico.
        $sql = 'SELECT 
                    a.id,
                    a.usuario_id,
                    u.nome AS usuario_nome,
                    a.pessoa_id,
                    p.nome AS pessoa_nome,
                    a.tipo_atendimento_id,
                    t.nome AS tipo_atendimento_nome,
                    a.descricao,
                    a.data_atendimento,
                    a.status,
                    a.horario_atendimento,
                    a.observacao_final,
                    a.criado_em
                FROM atendimentos a
                INNER JOIN usuarios u ON a.usuario_id = u.id
                INNER JOIN pessoas p ON a.pessoa_id = p.id
                INNER JOIN tipos_atendimentos t ON a.tipo_atendimento_id = t.id
                WHERE a.id = :id
                LIMIT 1';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $atendimento = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$atendimento) {
                http_response_code(404);
                echo json_encode(['erro' => 'Atendimento não encontrado.']);
                return;
            }

            echo json_encode($atendimento, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao buscar atendimento.']);
        }
    }

    public function criar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // Se usuario_id não for enviado no POST, tenta obter do usuário autenticado na sessão.
        $usuario_id = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT);
        if (!$usuario_id) {
            $usuario = usuarioAtual();
            $usuario_id = $usuario['id'] ?? null;
        }

        $pessoa_id = filter_input(INPUT_POST, 'pessoa_id', FILTER_VALIDATE_INT);
        $tipo_atendimento_id = filter_input(INPUT_POST, 'tipo_atendimento_id', FILTER_VALIDATE_INT);
        $descricao = trim($_POST['descricao'] ?? '');
        $data_atendimento = trim($_POST['data_atendimento'] ?? '');
        $horario_atendimento = trim($_POST['horario_atendimento'] ?? '');
        $status = $_POST['status'] ?? 'em_andamento';

        // Validação dos campos obrigatórios.
        if (!$usuario_id || !$pessoa_id || !$tipo_atendimento_id || $descricao === '') {
            http_response_code(400);
            echo json_encode(['erro' => 'Usuário, pessoa, tipo de atendimento e descrição são obrigatórios.']);
            return;
        }

        // Whitelist de status do atendimento.
        if (!in_array($status, ['em_andamento', 'concluido', 'cancelado'], true)) {
            http_response_code(400);
            echo json_encode(['erro' => 'Status inválido.']);
            return;
        }

        // Define valores padrão se não informados.
        $data_val = $data_atendimento !== '' ? $data_atendimento : date('Y-m-d H:i:s');
        $horario_val = $horario_atendimento !== '' ? $horario_atendimento : date('H:i:s');

        try {
            $sql = 'INSERT INTO atendimentos (usuario_id, pessoa_id, tipo_atendimento_id, descricao, data_atendimento, status, horario_atendimento)
                    VALUES (:usuario_id, :pessoa_id, :tipo_atendimento_id, :descricao, :data_val, :status, :horario_val)';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmt->bindValue(':pessoa_id', $pessoa_id, PDO::PARAM_INT);
            $stmt->bindValue(':tipo_atendimento_id', $tipo_atendimento_id, PDO::PARAM_INT);
            $stmt->bindValue(':descricao', $descricao);
            $stmt->bindValue(':data_val', $data_val);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':horario_val', $horario_val);
            $stmt->execute();

            http_response_code(201);
            echo json_encode([
                'mensagem' => 'Atendimento registrado com sucesso.',
                'id' => $this->pdo->lastInsertId()
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao registrar atendimento. Verifique se as referências de ID são válidas.']);
        }
    }

    public function alterarStatus(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $status = $_POST['status'] ?? '';
        $observacao_final = trim($_POST['observacao_final'] ?? '');

        if (!$id || $status === '') {
            http_response_code(400);
            echo json_encode(['erro' => 'ID e status são obrigatórios.']);
            return;
        }

        if (!in_array($status, ['em_andamento', 'concluido', 'cancelado'], true)) {
            http_response_code(400);
            echo json_encode(['erro' => 'Status inválido.']);
            return;
        }

        if ($status === 'concluido' && $observacao_final === '') {
            http_response_code(400);
            echo json_encode(['erro' => 'A observação final é obrigatória ao concluir o atendimento.']);
            return;
        }

        try {
            $sql = 'UPDATE atendimentos
                    SET status = :status,
                        observacao_final = :observacao_final
                    WHERE id = :id';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':observacao_final', $observacao_final !== '' ? $observacao_final : null);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode(['mensagem' => 'Status do atendimento atualizado com sucesso.'], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao atualizar status do atendimento.']);
        }
    }
}
