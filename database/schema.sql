-- Database Schema for SahamPintar (MySQL / MariaDB)

CREATE DATABASE IF NOT EXISTS sahampintar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sahampintar;

-- Tabel Saham (Cache dari Yahoo Finance)
CREATE TABLE IF NOT EXISTS stocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,      -- e.g., BBCA
    yahoo_code VARCHAR(15),                 -- e.g., BBCA.JK
    name VARCHAR(255),
    sector VARCHAR(100),
    sub_sector VARCHAR(100),
    market_cap BIGINT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel Data Harga Harian (Cache)
CREATE TABLE IF NOT EXISTS stock_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stock_code VARCHAR(10) NOT NULL,
    date DATE NOT NULL,
    open DECIMAL(12,2),
    high DECIMAL(12,2),
    low DECIMAL(12,2),
    close DECIMAL(12,2),
    adj_close DECIMAL(12,2),
    volume BIGINT,
    UNIQUE KEY uk_stock_date (stock_code, date),
    INDEX idx_date (date),
    INDEX idx_code (stock_code)
);

-- Tabel Sinyal Trading
CREATE TABLE IF NOT EXISTS signals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stock_code VARCHAR(10) NOT NULL,
    signal_type ENUM('STRONG_BUY','BUY','HOLD','SELL','STRONG_SELL') NOT NULL,
    confidence TINYINT UNSIGNED,            -- 0-100
    entry_price DECIMAL(12,2),
    target_1 DECIMAL(12,2),
    target_2 DECIMAL(12,2),
    stop_loss DECIMAL(12,2),
    risk_reward_ratio VARCHAR(10),
    strategy VARCHAR(50),
    reasoning TEXT,
    indicators_data JSON,                   -- Raw indicator values
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Logbook (Lokal mirror — synced ke Google Sheets)
CREATE TABLE IF NOT EXISTS logbook (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stock_code VARCHAR(10),
    action ENUM('BUY','SELL','WATCH','ANALYSIS') NOT NULL,
    entry_price DECIMAL(12,2),
    exit_price DECIMAL(12,2),
    quantity INT DEFAULT 0,
    pnl DECIMAL(12,2),
    pnl_percent DECIMAL(6,2),
    notes TEXT,
    tags VARCHAR(255),
    sheet_synced BOOLEAN DEFAULT FALSE,
    sheet_row_id INT,                       -- Row number di Google Sheets
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Portofolio
CREATE TABLE IF NOT EXISTS portfolio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stock_code VARCHAR(10) NOT NULL,
    avg_price DECIMAL(12,2),
    quantity INT,
    total_invested DECIMAL(15,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel Watchlist
CREATE TABLE IF NOT EXISTS watchlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stock_code VARCHAR(10) NOT NULL,
    notes TEXT,
    alert_price_above DECIMAL(12,2),
    alert_price_below DECIMAL(12,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Backtest Results
CREATE TABLE IF NOT EXISTS backtest_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    strategy_name VARCHAR(100),
    strategy_config JSON,                   -- Parameter strategi
    stock_code VARCHAR(10),
    period_start DATE,
    period_end DATE,
    total_trades INT,
    win_rate DECIMAL(5,2),
    total_return DECIMAL(8,2),
    max_drawdown DECIMAL(8,2),
    sharpe_ratio DECIMAL(5,2),
    avg_hold_days DECIMAL(5,1),
    trades_data JSON,                       -- Detail semua trades
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Daftar Saham IDX (Master data)
CREATE TABLE IF NOT EXISTS idx_stocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(255),
    sector VARCHAR(100),
    listing_date DATE,
    shares_outstanding BIGINT
);
