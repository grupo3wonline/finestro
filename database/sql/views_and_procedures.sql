-- ============================================
-- Finestro - Database Views and Stored Procedures
-- ============================================

-- View: Monthly summary per user
DROP VIEW IF EXISTS vw_monthly_summary;
CREATE VIEW vw_monthly_summary AS
SELECT
    t.user_id,
    YEAR(t.date) AS year,
    MONTH(t.date) AS month,
    SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END) AS total_income,
    SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END) AS total_expenses,
    SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE -t.amount END) AS balance,
    COUNT(*) AS transaction_count
FROM transactions t
WHERE t.deleted_at IS NULL
GROUP BY t.user_id, YEAR(t.date), MONTH(t.date);

-- View: Wallet balances with transaction counts
DROP VIEW IF EXISTS vw_wallet_overview;
CREATE VIEW vw_wallet_overview AS
SELECT
    w.id AS wallet_id,
    w.user_id,
    w.name AS wallet_name,
    w.type AS wallet_type,
    w.balance,
    w.is_active,
    COUNT(t.id) AS transaction_count,
    MAX(t.date) AS last_transaction_date
FROM wallets w
LEFT JOIN transactions t ON t.wallet_id = w.id AND t.deleted_at IS NULL
WHERE w.deleted_at IS NULL
GROUP BY w.id, w.user_id, w.name, w.type, w.balance, w.is_active;

-- View: Goal progress overview
DROP VIEW IF EXISTS vw_goal_progress;
CREATE VIEW vw_goal_progress AS
SELECT
    g.id AS goal_id,
    g.user_id,
    g.name,
    g.target_amount,
    g.current_amount,
    g.deadline,
    g.status,
    ROUND((g.current_amount / g.target_amount) * 100, 2) AS progress_percentage,
    DATEDIFF(g.deadline, CURDATE()) AS days_remaining
FROM goals g
WHERE g.deleted_at IS NULL;

-- Stored Procedure: Get user financial summary
DELIMITER //
DROP PROCEDURE IF EXISTS sp_user_financial_summary//
CREATE PROCEDURE sp_user_financial_summary(IN p_user_id CHAR(36))
BEGIN
    SELECT
        (SELECT COALESCE(SUM(balance), 0) FROM wallets WHERE user_id = p_user_id AND is_active = 1 AND deleted_at IS NULL) AS total_balance,
        (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE user_id = p_user_id AND type = 'income' AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE()) AND deleted_at IS NULL) AS monthly_income,
        (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE user_id = p_user_id AND type = 'expense' AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE()) AND deleted_at IS NULL) AS monthly_expenses,
        (SELECT COUNT(*) FROM wallets WHERE user_id = p_user_id AND is_active = 1 AND deleted_at IS NULL) AS active_wallets,
        (SELECT COUNT(*) FROM goals WHERE user_id = p_user_id AND status = 'active' AND deleted_at IS NULL) AS active_goals;
END//
DELIMITER ;

-- Stored Procedure: Recalculate wallet balance
DELIMITER //
DROP PROCEDURE IF EXISTS sp_recalculate_wallet_balance//
CREATE PROCEDURE sp_recalculate_wallet_balance(IN p_wallet_id CHAR(36))
BEGIN
    DECLARE v_balance DECIMAL(15,2);

    SELECT COALESCE(
        SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END), 0
    ) INTO v_balance
    FROM transactions
    WHERE wallet_id = p_wallet_id AND deleted_at IS NULL;

    UPDATE wallets SET balance = v_balance WHERE id = p_wallet_id;
END//
DELIMITER ;

-- Trigger: Update goal status when target is reached
DELIMITER //
DROP TRIGGER IF EXISTS trg_goal_completion//
CREATE TRIGGER trg_goal_completion
BEFORE UPDATE ON goals
FOR EACH ROW
BEGIN
    IF NEW.current_amount >= NEW.target_amount AND NEW.status = 'active' THEN
        SET NEW.status = 'completed';
    END IF;
END//
DELIMITER ;
