CREATE DATABASE IF NOT EXISTS tiaozinho_pesquisa;
USE tiaozinho_pesquisa;

CREATE TABLE pesquisas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    loja_preferida VARCHAR(20),
    canais_promocao TEXT,
    outros_canais_especificar VARCHAR(255),
    nps INT NOT NULL,
    motivo_visita VARCHAR(50),
    encontrou_tudo VARCHAR(3),
    o_que_faltou TEXT,
    avaliacao_preco INT,
    avaliacao_fila INT,
    avaliacao_qualidade INT,
    avaliacao_limpeza INT,
    avaliacao_atendimento INT,
    avaliacao_satisfacao INT,
    voltar_amanha TEXT,
    receber_ofertas BOOLEAN DEFAULT 0,
    aceite_lgpd BOOLEAN DEFAULT 1,
    data_pesquisa DATETIME NOT NULL,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_data (data_pesquisa),
    INDEX idx_nps (nps),
    INDEX idx_email (email),
    INDEX idx_loja (loja_preferida)
);

CREATE TABLE usuarios_admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    ativo BOOLEAN DEFAULT 1,
    ultimo_acesso DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inserir usuário admin padrão (senha: admin123)
INSERT INTO usuarios_admin (username, password, nome, email) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin@tiaozinho.com');

CREATE TABLE configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(50) UNIQUE NOT NULL,
    valor TEXT,
    descricao VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO configuracoes (chave, valor, descricao) VALUES
('email_smtp_host', 'smtp.gmail.com', 'Servidor SMTP'),
('email_smtp_port', '587', 'Porta SMTP'),
('email_smtp_user', 'pesquisa@tiaozinho.com', 'Usuário SMTP'),
('email_smtp_pass', '1234qwer!@#$QWER', 'Senha SMTP'),
('email_from', 'pesquisa@tiaozinho.com', 'Email remetente'),
('email_admin', 'admin@tiaozinho.com', 'Email do administrador');