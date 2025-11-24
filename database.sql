CREATE DATABASE IF NOT EXISTS celular_rentals CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE celular_rentals;

CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    cnpj VARCHAR(30),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    telefone VARCHAR(40),
    tipo ENUM('cliente','admin') NOT NULL DEFAULT 'cliente',
    company_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE hoses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    serial VARCHAR(120) NOT NULL UNIQUE,
    tipo VARCHAR(120) NOT NULL,
    bitola VARCHAR(60) NOT NULL,
    pressao VARCHAR(60) NOT NULL,
    comprimento VARCHAR(60) NOT NULL,
    marca VARCHAR(120),
    data_compra DATE,
    foto VARCHAR(255),
    status ENUM('disponivel','alugado','atrasado','em uso','manutencao','condenado') DEFAULT 'disponivel',
    observacoes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE parts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    serial VARCHAR(120) NOT NULL UNIQUE,
    tipo VARCHAR(120) NOT NULL,
    aplicacao VARCHAR(120),
    marca VARCHAR(120),
    data_compra DATE,
    foto VARCHAR(255),
    status ENUM('disponivel','alugado','atrasado','em uso','manutencao','condenado') DEFAULT 'disponivel',
    observacoes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE rentals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_type ENUM('hose','part') NOT NULL,
    item_id INT NOT NULL,
    data_saida DATE NOT NULL,
    previsao_retorno DATE NOT NULL,
    data_retorno DATE NULL,
    status ENUM('alugado','em uso','atrasado','devolvido','manutencao','condenado') DEFAULT 'alugado',
    condicao_entrega VARCHAR(255),
    observacoes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE maintenance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_type ENUM('hose','part') NOT NULL,
    item_id INT NOT NULL,
    data_abertura DATETIME NOT NULL,
    data_prevista DATE NOT NULL,
    data_conclusao DATE NULL,
    motivo VARCHAR(255) NOT NULL,
    checklist TEXT,
    custo DECIMAL(10,2) DEFAULT 0,
    status ENUM('aberta','em andamento','concluida','cancelada') DEFAULT 'aberta',
    fotos TEXT,
    observacoes TEXT
) ENGINE=InnoDB;

CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_type ENUM('hose','part') NOT NULL,
    item_id INT NOT NULL,
    descricao TEXT NOT NULL,
    foto VARCHAR(255),
    urgencia ENUM('baixa','media','alta') DEFAULT 'media',
    status ENUM('aberto','em analise','em manutencao','concluido','cancelado') DEFAULT 'aberto',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO companies (nome, cnpj) VALUES ('Empresa Demo', '00.000.000/0000-00');

INSERT INTO users (nome, email, senha_hash, telefone, tipo, company_id) VALUES
('Admin', 'admin@empresa.com', '$2y$12$HyXNg/b/BYqYAjN7IcM/F.jdW/u2YmDE9sQ6Vsbg3Ij9fFsCYYYIC', '0000-0000', 'admin', 1),
('Cliente Demo', 'user@empresa.com', '$2y$12$6amp5j3LYkbA6liGT.tKFeYdkFCJYo9vpal.TYsRGjWl1/2hDLXb.', '11999999999', 'cliente', 1);
