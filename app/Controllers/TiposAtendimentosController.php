<?php
// Controller da entidade de tipos de atendimento.
// Em uma arquitetura MVC, ele recebe a requisição, valida dados e acessa o banco.
class TiposAtendimentosController
{
    // Conexão PDO reutilizada em todos os métodos.
    private PDO $pdo;

    public function __construct()
    {
        // Importa o arquivo que inicializa o objeto $pdo.
        require __DIR__ . '/../../config/database.php';
        $this->pdo = $pdo;
    }

    public function listar(): void
    {
        // Define saída em JSON para APIs/consumo por front-end.
        header('Content-Type: application/json; charset=utf-8');

        // Consulta todos os tipos com ordenação decrescente por ID.
        $sql = 'SELECT id, nome, descricao, status, criado_em
                FROM tipos_atendimentos
                ORDER BY id DESC';

        $stmt = $this->pdo->query($sql);
        $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // JSON_PRETTY_PRINT melhora leitura em desenvolvimento.
        echo json_encode($tipos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function buscar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // Lê e valida o ID recebido por GET.
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['erro' => 'ID inválido.']);
            return;
        }

        // Consulta parametrizada evita SQL Injection.
        $sql = 'SELECT id, nome, descricao, status, criado_em
                FROM tipos_atendimentos
                WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $tipo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tipo) {
            http_response_code(404);
            echo json_encode(['erro' => 'Tipo de atendimento não encontrado.']);
            return;
        }

        echo json_encode($tipo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function criar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // Coleta dados do formulário (POST).
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $status = $_POST['status'] ?? 'ativo';

        // Regras mínimas de validação de entrada.
        if ($nome === '') {
            http_response_code(400);
            echo json_encode(['erro' => 'Nome é obrigatório.']);
            return;
        }

        // Whitelist de valores válidos para status.
        if (!in_array($status, ['ativo', 'inativo'], true)) {
            http_response_code(400);
            echo json_encode(['erro' => 'Status inválido.']);
            return;
        }

        try {
            $sql = 'INSERT INTO tipos_atendimentos (nome, descricao, status)
                    VALUES (:nome, :descricao, :status)';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':nome', $nome);
            $stmt->bindValue(':descricao', $descricao ?: null);
            $stmt->bindValue(':status', $status);
            $stmt->execute();

            http_response_code(201);
            echo json_encode([
                'mensagem' => 'Tipo de atendimento cadastrado com sucesso.',
                'id' => $this->pdo->lastInsertId()
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao cadastrar tipo de atendimento.']);
        }
    }

    public function atualizar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // ID vem no POST para operação de update.
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $status = $_POST['status'] ?? 'ativo';

        if (!$id || $nome === '') {
            http_response_code(400);
            echo json_encode(['erro' => 'ID e nome são obrigatórios.']);
            return;
        }

        if (!in_array($status, ['ativo', 'inativo'], true)) {
            http_response_code(400);
            echo json_encode(['erro' => 'Status inválido.']);
            return;
        }

        try {
            $sql = 'UPDATE tipos_atendimentos
                    SET nome = :nome,
                        descricao = :descricao,
                        status = :status
                    WHERE id = :id';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':nome', $nome);
            $stmt->bindValue(':descricao', $descricao ?: null);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode(['mensagem' => 'Tipo de atendimento atualizado com sucesso.'], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao atualizar tipo de atendimento.']);
        }
    }

    public function inativar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // Inativação por ID recebido no corpo da requisição.
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['erro' => 'ID inválido.']);
            return;
        }

        try {
            // Inativa o tipo de atendimento (soft delete) em vez de deletar.
            $sql = 'UPDATE tipos_atendimentos SET status = :status WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':status', 'inativo');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode(['mensagem' => 'Tipo de atendimento inativado com sucesso.'], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao inativar tipo de atendimento.']);
        }
    }
}
