<?php
// Controller da entidade de pessoas.
// Em uma arquitetura MVC, ele recebe a requisição, valida dados e acessa o banco.
class PessoasController
{
    // Conexão PDO reutilizada em todos os métodos.
    private PDO $pdo;

    private function somenteDigitos(?string $valor): string
    {
        return preg_replace('/\D+/', '', $valor ?? '') ?? '';
    }

    private function formatarTelefone(string $digitos): string
    {
        return sprintf('(%s) %s-%s', substr($digitos, 0, 2), substr($digitos, 2, 5), substr($digitos, 7, 4));
    }

    private function validarDocumento(string $documento): ?string
    {
        if ($documento === '') {
            return null;
        }

        if (strlen($documento) !== 11) {
            return 'Documento deve conter 11 dígitos do CPF.';
        }

        return null;
    }

    private function validarTelefone(string $telefone): ?string
    {
        if ($telefone === '') {
            return null;
        }

        if (strlen($telefone) !== 11) {
            return 'Telefone deve conter DDD e número no formato (47) 99999-9999.';
        }

        return null;
    }

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
        $controllerAtual = 'pessoas';
        $tituloPagina = 'Pessoas atendidas';

        require __DIR__ . '/../Views/pessoas/index.php';
    }

    public function listar(): void
    {
        // Define saída em JSON para APIs/consumo por front-end.
        header('Content-Type: application/json; charset=utf-8');

        // Consulta todas as pessoas com ordenação decrescente por ID.
        $sql = 'SELECT id, nome, documento, email, telefone, status, criado_em, curso, periodo, observacoes
                FROM pessoas
                ORDER BY id DESC';

        $stmt = $this->pdo->query($sql);
        $pessoas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // JSON_PRETTY_PRINT melhora leitura em desenvolvimento.
        echo json_encode($pessoas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
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
        $sql = 'SELECT id, nome, documento, email, telefone, status, criado_em, curso, periodo, observacoes
                FROM pessoas
                WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $pessoa = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pessoa) {
            http_response_code(404);
            echo json_encode(['erro' => 'Pessoa não encontrada.']);
            return;
        }

        echo json_encode($pessoa, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function criar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // Coleta dados do formulário (POST).
        $nome = trim($_POST['nome'] ?? '');
        $documento = trim($_POST['documento'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $status = $_POST['status'] ?? 'ativo';
        $curso = trim($_POST['curso'] ?? '');
        $periodo = trim($_POST['periodo'] ?? '');
        $observacoes = trim($_POST['observacoes'] ?? '');

        // Regras mínimas de validação de entrada.
        if ($nome === '') {
            http_response_code(400);
            echo json_encode(['erro' => 'Nome é obrigatório.']);
            return;
        }

        $documento = $this->somenteDigitos($documento);
        $telefone = $this->somenteDigitos($telefone);

        if ($erroDocumento = $this->validarDocumento($documento)) {
            http_response_code(400);
            echo json_encode(['erro' => $erroDocumento]);
            return;
        }

        if ($erroTelefone = $this->validarTelefone($telefone)) {
            http_response_code(400);
            echo json_encode(['erro' => $erroTelefone]);
            return;
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['erro' => 'E-mail inválido.']);
            return;
        }

        // Whitelist de valores válidos para status.
        if (!in_array($status, ['ativo', 'inativo'], true)) {
            http_response_code(400);
            echo json_encode(['erro' => 'Status inválido.']);
            return;
        }

        try {
            $sql = 'INSERT INTO pessoas (nome, documento, email, telefone, status, curso, periodo, observacoes)
                    VALUES (:nome, :documento, :email, :telefone, :status, :curso, :periodo, :observacoes)';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':nome', $nome);
            $stmt->bindValue(':documento', $documento ?: null);
            $stmt->bindValue(':email', $email ?: null);
            $stmt->bindValue(':telefone', $telefone ? $this->formatarTelefone($telefone) : null);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':curso', $curso ?: null);
            $stmt->bindValue(':periodo', $periodo ?: null);
            $stmt->bindValue(':observacoes', $observacoes ?: null);
            $stmt->execute();

            http_response_code(201);
            echo json_encode([
                'mensagem' => 'Pessoa cadastrada com sucesso.',
                'id' => $this->pdo->lastInsertId()
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            // Em produção, registre $e em log em vez de expor detalhes.
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao cadastrar pessoa.']);
        }
    }

    public function atualizar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // ID vem no POST para operação de update.
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nome = trim($_POST['nome'] ?? '');
        $documento = trim($_POST['documento'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $status = $_POST['status'] ?? 'ativo';
        $curso = trim($_POST['curso'] ?? '');
        $periodo = trim($_POST['periodo'] ?? '');
        $observacoes = trim($_POST['observacoes'] ?? '');

        if (!$id || $nome === '') {
            http_response_code(400);
            echo json_encode(['erro' => 'ID e nome são obrigatórios.']);
            return;
        }

        $documento = $this->somenteDigitos($documento);
        $telefone = $this->somenteDigitos($telefone);

        if ($erroDocumento = $this->validarDocumento($documento)) {
            http_response_code(400);
            echo json_encode(['erro' => $erroDocumento]);
            return;
        }

        if ($erroTelefone = $this->validarTelefone($telefone)) {
            http_response_code(400);
            echo json_encode(['erro' => $erroTelefone]);
            return;
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['erro' => 'E-mail inválido.']);
            return;
        }

        if (!in_array($status, ['ativo', 'inativo'], true)) {
            http_response_code(400);
            echo json_encode(['erro' => 'Status inválido.']);
            return;
        }

        try {
            $sql = 'UPDATE pessoas
                    SET nome = :nome,
                        documento = :documento,
                        email = :email,
                        telefone = :telefone,
                        status = :status,
                        curso = :curso,
                        periodo = :periodo,
                        observacoes = :observacoes
                    WHERE id = :id';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':nome', $nome);
            $stmt->bindValue(':documento', $documento ?: null);
            $stmt->bindValue(':email', $email ?: null);
            $stmt->bindValue(':telefone', $telefone ? $this->formatarTelefone($telefone) : null);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':curso', $curso ?: null);
            $stmt->bindValue(':periodo', $periodo ?: null);
            $stmt->bindValue(':observacoes', $observacoes ?: null);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode(['mensagem' => 'Pessoa atualizada com sucesso.'], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao atualizar pessoa.']);
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
            // Inativa a pessoa (soft delete) em vez de deletar.
            $sql = 'UPDATE pessoas SET status = :status WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':status', 'inativo');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode(['mensagem' => 'Pessoa inativada com sucesso.'], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao inativar pessoa.']);
        }
    }
}
