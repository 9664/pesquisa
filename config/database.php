<?php
class Database {
    // CONFIGURAÇÕES CORRETAS DO BANCO DE DADOS
    private $host = "localhost";
    private $db_name = "tiaozinh_pesquisa";  // Nome correto conforme informado
    private $username = "tiaozinh_pesquisa"; // Usuário correto conforme informado
    private $password = "1234qwer!@#$QWER";  // Senha correta conforme informada
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            // Configuração com charset UTF-8 e opções de segurança
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $exception) {
            // Log do erro sem expor informações sensíveis
            error_log("Erro de conexão com banco de dados: " . $exception->getMessage());
            
            // Em produção, não exibir detalhes do erro
            if (defined('DEBUG') && DEBUG === true) {
                echo "Erro de conexão: " . $exception->getMessage();
            } else {
                echo "Erro de conexão com o banco de dados. Tente novamente mais tarde.";
            }
        }
        return $this->conn;
    }
    
    /**
     * Testa a conexão com o banco de dados
     * @return bool
     */
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            if ($conn !== null) {
                // Teste simples de query
                $stmt = $conn->query("SELECT 1");
                return $stmt !== false;
            }
            return false;
        } catch (Exception $e) {
            error_log("Teste de conexão falhou: " . $e->getMessage());
            return false;
        }
    }
}
?>
