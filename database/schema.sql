-- =====================================================
-- SCHEMA PARA BANCO EXISTENTE - Sistema de Pesquisa Tiãozinho
-- Caminho: database/schema.sql
-- =====================================================

-- Usar banco existente (nome correto)
USE tiaozinh_pesquisa;

-- Tabela principal de pesquisas
CREATE TABLE IF NOT EXISTS pesquisas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    loja_preferida VARCHAR(20) NOT NULL,
    canais_promocao TEXT NOT NULL,
    outros_canais_especificar VARCHAR(255),
    nps INT NOT NULL CHECK (nps >= 0 AND nps <= 10),
    motivo_visita VARCHAR(50) NOT NULL,
    encontrou_tudo ENUM('sim', 'nao') NOT NULL,
    o_que_faltou TEXT,
    avaliacao_preco INT CHECK (avaliacao_preco >= 1 AND avaliacao_preco <= 5),
    avaliacao_fila INT CHECK (avaliacao_fila >= 1 AND avaliacao_fila <= 5),
    avaliacao_qualidade INT CHECK (avaliacao_qualidade >= 1 AND avaliacao_qualidade <= 5),
    avaliacao_limpeza INT CHECK (avaliacao_limpeza >= 1 AND avaliacao_limpeza <= 5),
    avaliacao_atendimento INT CHECK (avaliacao_atendimento >= 1 AND avaliacao_atendimento <= 5),
    avaliacao_satisfacao INT CHECK (avaliacao_satisfacao >= 1 AND avaliacao_satisfacao <= 5),
    voltar_amanha TEXT,
    receber_ofertas BOOLEAN DEFAULT 0,
    aceite_lgpd BOOLEAN DEFAULT 1,
    data_pesquisa DATETIME NOT NULL,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices para performance
    INDEX idx_data_pesquisa (data_pesquisa),
    INDEX idx_nps (nps),
    INDEX idx_email (email),
    INDEX idx_loja_preferida (loja_preferida),
    INDEX idx_created_at (created_at),
    INDEX idx_nps_loja (nps, loja_preferida)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de usuários administrativos
CREATE TABLE IF NOT EXISTS usuarios_admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    ativo BOOLEAN DEFAULT 1,
    ultimo_acesso DATETIME,
    tentativas_login INT DEFAULT 0,
    bloqueado_ate DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de configurações do sistema
CREATE TABLE IF NOT EXISTS configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(50) UNIQUE NOT NULL,
    valor TEXT,
    descricao VARCHAR(255),
    tipo ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    categoria VARCHAR(50) DEFAULT 'geral',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_chave (chave),
    INDEX idx_categoria (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de logs do sistema
CREATE TABLE IF NOT EXISTS logs_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('info', 'warning', 'error', 'debug') NOT NULL,
    modulo VARCHAR(50) NOT NULL,
    mensagem TEXT NOT NULL,
    dados_extras JSON,
    usuario_id INT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_tipo (tipo),
    INDEX idx_modulo (modulo),
    INDEX idx_created_at (created_at),
    INDEX idx_usuario_id (usuario_id),
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios_admin(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DADOS INICIAIS
-- =====================================================

-- Inserir usuário admin padrão (se não existir)
-- Senha: Admin123! (hash gerado com password_hash)
INSERT IGNORE INTO usuarios_admin (username, password, nome, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin@tiaozinho.com');

-- Configurações padrão do sistema (inserir apenas se não existirem)
INSERT IGNORE INTO configuracoes (chave, valor, descricao, tipo, categoria) VALUES
-- Configurações de Email
('email_smtp_host', 'mail.tiaozinho.com', 'Servidor SMTP', 'string', 'email'),
('email_smtp_port', '587', 'Porta SMTP', 'number', 'email'),
('email_smtp_user', 'pesquisa@tiaozinho.com', 'Usuário SMTP', 'string', 'email'),
('email_smtp_pass', '', 'Senha SMTP (criptografada)', 'string', 'email'),
('email_from', 'pesquisa@tiaozinho.com', 'Email remetente', 'string', 'email'),
('email_from_name', 'Tiãozinho Supermercados', 'Nome remetente', 'string', 'email'),
('email_admin', 'admin@tiaozinho.com', 'Email do administrador', 'string', 'email'),

-- Configurações do Sistema
('sistema_nome', 'Sistema de Pesquisa Tiãozinho', 'Nome do sistema', 'string', 'sistema'),
('sistema_versao', '1.0.0', 'Versão do sistema', 'string', 'sistema'),
('debug_mode', 'false', 'Modo debug ativo', 'boolean', 'sistema'),
('manutencao_mode', 'false', 'Modo manutenção ativo', 'boolean', 'sistema'),

-- Configurações de Segurança
('max_tentativas_login', '5', 'Máximo de tentativas de login', 'number', 'seguranca'),
('tempo_bloqueio_login', '30', 'Tempo de bloqueio em minutos', 'number', 'seguranca'),
('sessao_timeout', '3600', 'Timeout da sessão em segundos', 'number', 'seguranca'),

-- Configurações da Pesquisa
('pesquisa_ativa', 'true', 'Pesquisa ativa para recebimento', 'boolean', 'pesquisa'),
('enviar_email_agradecimento', 'true', 'Enviar email de agradecimento', 'boolean', 'pesquisa'),
('enviar_notificacao_admin', 'true', 'Enviar notificação para admin', 'boolean', 'pesquisa'),
('limite_pesquisas_por_ip', '5', 'Limite de pesquisas por IP por dia', 'number', 'pesquisa');

-- =====================================================
-- VIEWS ÚTEIS PARA RELATÓRIOS
-- =====================================================

-- View para estatísticas de NPS por loja e data
CREATE OR REPLACE VIEW vw_estatisticas_nps AS
SELECT 
    DATE(data_pesquisa) as data,
    loja_preferida,
    COUNT(*) as total_respostas,
    ROUND(AVG(nps), 2) as nps_medio,
    SUM(CASE WHEN nps >= 9 THEN 1 ELSE 0 END) as promotores,
    SUM(CASE WHEN nps >= 7 AND nps <= 8 THEN 1 ELSE 0 END) as neutros,
    SUM(CASE WHEN nps <= 6 THEN 1 ELSE 0 END) as detratores,
    ROUND(
        (SUM(CASE WHEN nps >= 9 THEN 1 ELSE 0 END) - SUM(CASE WHEN nps <= 6 THEN 1 ELSE 0 END)) * 100.0 / COUNT(*), 
        2
    ) as nps_score
FROM pesquisas 
GROUP BY DATE(data_pesquisa), loja_preferida;

-- View para avaliações médias por loja
CREATE OR REPLACE VIEW vw_avaliacoes_por_loja AS
SELECT 
    loja_preferida,
    COUNT(*) as total_avaliacoes,
    ROUND(AVG(avaliacao_preco), 2) as media_preco,
    ROUND(AVG(avaliacao_fila), 2) as media_fila,
    ROUND(AVG(avaliacao_qualidade), 2) as media_qualidade,
    ROUND(AVG(avaliacao_limpeza), 2) as media_limpeza,
    ROUND(AVG(avaliacao_atendimento), 2) as media_atendimento,
    ROUND(AVG(avaliacao_satisfacao), 2) as media_satisfacao,
    ROUND(AVG(nps), 2) as nps_medio
FROM pesquisas 
GROUP BY loja_preferida;

-- View para resumo diário
CREATE OR REPLACE VIEW vw_resumo_diario AS
SELECT 
    DATE(data_pesquisa) as data,
    COUNT(*) as total_pesquisas,
    ROUND(AVG(nps), 2) as nps_medio,
    ROUND(AVG(avaliacao_satisfacao), 2) as satisfacao_media,
    SUM(CASE WHEN nps >= 9 THEN 1 ELSE 0 END) as promotores,
    SUM(CASE WHEN nps <= 6 THEN 1 ELSE 0 END) as detratores,
    COUNT(DISTINCT loja_preferida) as lojas_avaliadas
FROM pesquisas 
GROUP BY DATE(data_pesquisa)
ORDER BY data DESC;

-- =====================================================
-- PROCEDURES ÚTEIS
-- =====================================================

DELIMITER //

-- Procedure para limpeza de logs antigos
DROP PROCEDURE IF EXISTS LimparLogsAntigos//
CREATE PROCEDURE LimparLogsAntigos(IN dias_manter INT)
BEGIN
    DELETE FROM logs_sistema 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL dias_manter DAY);
    
    SELECT ROW_COUNT() as registros_removidos;
END //

-- Procedure para estatísticas rápidas do dashboard
DROP PROCEDURE IF EXISTS EstatisticasRapidas//
CREATE PROCEDURE EstatisticasRapidas()
BEGIN
    SELECT 
        'Total de Pesquisas' as metrica,
        COUNT(*) as valor,
        'success' as tipo
    FROM pesquisas
    
    UNION ALL
    
    SELECT 
        'Pesquisas Hoje' as metrica,
        COUNT(*) as valor,
        'info' as tipo
    FROM pesquisas 
    WHERE DATE(data_pesquisa) = CURDATE()
    
    UNION ALL
    
    SELECT 
        'NPS Médio Geral' as metrica,
        ROUND(AVG(nps), 2) as valor,
        CASE 
            WHEN AVG(nps) >= 8 THEN 'success'
            WHEN AVG(nps) >= 6 THEN 'warning'
            ELSE 'danger'
        END as tipo
    FROM pesquisas
    
    UNION ALL
    
    SELECT 
        'Satisfação Média' as metrica,
        ROUND(AVG(avaliacao_satisfacao), 2) as valor,
        CASE 
            WHEN AVG(avaliacao_satisfacao) >= 4 THEN 'success'
            WHEN AVG(avaliacao_satisfacao) >= 3 THEN 'warning'
            ELSE 'danger'
        END as tipo
    FROM pesquisas
    
    UNION ALL
    
    SELECT 
        'Pesquisas Esta Semana' as metrica,
        COUNT(*) as valor,
        'primary' as tipo
    FROM pesquisas 
    WHERE YEARWEEK(data_pesquisa, 1) = YEARWEEK(CURDATE(), 1);
END //

-- Procedure para relatório de NPS por período
DROP PROCEDURE IF EXISTS RelatorioNPSPeriodo//
CREATE PROCEDURE RelatorioNPSPeriodo(IN data_inicio DATE, IN data_fim DATE)
BEGIN
    SELECT 
        loja_preferida,
        COUNT(*) as total_respostas,
        ROUND(AVG(nps), 2) as nps_medio,
        SUM(CASE WHEN nps >= 9 THEN 1 ELSE 0 END) as promotores,
        SUM(CASE WHEN nps >= 7 AND nps <= 8 THEN 1 ELSE 0 END) as neutros,
        SUM(CASE WHEN nps <= 6 THEN 1 ELSE 0 END) as detratores,
        ROUND(
            (SUM(CASE WHEN nps >= 9 THEN 1 ELSE 0 END) - SUM(CASE WHEN nps <= 6 THEN 1 ELSE 0 END)) * 100.0 / COUNT(*), 
            2
        ) as nps_score
    FROM pesquisas 
    WHERE DATE(data_pesquisa) BETWEEN data_inicio AND data_fim
    GROUP BY loja_preferida
    ORDER BY nps_score DESC;
END //

DELIMITER ;

-- =====================================================
-- TRIGGERS PARA AUDITORIA
-- =====================================================

DELIMITER //

-- Trigger para log de inserção de pesquisas
DROP TRIGGER IF EXISTS tr_pesquisa_inserida//
CREATE TRIGGER tr_pesquisa_inserida
AFTER INSERT ON pesquisas
FOR EACH ROW
BEGIN
    INSERT INTO logs_sistema (tipo, modulo, mensagem, dados_extras)
    VALUES (
        'info', 
        'pesquisa', 
        CONCAT('Nova pesquisa recebida - ID: ', NEW.id),
        JSON_OBJECT(
            'pesquisa_id', NEW.id,
            'nome', NEW.nome,
            'email', NEW.email,
            'nps', NEW.nps,
            'loja', NEW.loja_preferida,
            'ip', NEW.ip_address
        )
    );
END //

-- Trigger para log de login de usuários admin
DROP TRIGGER IF EXISTS tr_admin_login//
CREATE TRIGGER tr_admin_login
AFTER UPDATE ON usuarios_admin
FOR EACH ROW
BEGIN
    IF NEW.ultimo_acesso != OLD.ultimo_acesso THEN
        INSERT INTO logs_sistema (tipo, modulo, mensagem, dados_extras, usuario_id)
        VALUES (
            'info', 
            'admin', 
            CONCAT('Login realizado - Usuário: ', NEW.username),
            JSON_OBJECT(
                'usuario_id', NEW.id,
                'username', NEW.username,
                'ultimo_acesso', NEW.ultimo_acesso
            ),
            NEW.id
        );
    END IF;
END //

DELIMITER ;

-- =====================================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- =====================================================

-- Índices compostos para consultas frequentes
CREATE INDEX IF NOT EXISTS idx_data_loja ON pesquisas(data_pesquisa, loja_preferida);
CREATE INDEX IF NOT EXISTS idx_nps_data ON pesquisas(nps, data_pesquisa);
CREATE INDEX IF NOT EXISTS idx_email_data ON pesquisas(email, data_pesquisa);

-- Índices para logs
CREATE INDEX IF NOT EXISTS idx_logs_data_tipo ON logs_sistema(created_at, tipo);
CREATE INDEX IF NOT EXISTS idx_logs_modulo_data ON logs_sistema(modulo, created_at);

-- =====================================================
-- COMENTÁRIOS FINAIS
-- =====================================================

-- Este schema foi criado para:
-- 1. Trabalhar com o banco existente tiaozinh_pesquisa
-- 2. Usar CREATE TABLE IF NOT EXISTS para evitar erros
-- 3. Incluir INSERT IGNORE para dados iniciais
-- 4. Criar views úteis para relatórios
-- 5. Incluir procedures para operações comuns
-- 6. Adicionar triggers para auditoria
-- 7. Otimizar com índices apropriados
-- 8. Manter compatibilidade com a estrutura existente
