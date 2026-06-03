DELIMITER //

CREATE PROCEDURE sp_user_financial_summary(IN p_user_id CHAR(36))
BEGIN
    SELECT
        (SELECT COALESCE(SUM(balance), 0) FROM wallets WHERE user_id = p_user_id AND is_active = 1 AND deleted_at IS NULL) AS total_balance,
        (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE user_id = p_user_id AND type = 'income' AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE()) AND deleted_at IS NULL) AS monthly_income,
        (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE user_id = p_user_id AND type = 'expense' AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE()) AND deleted_at IS NULL) AS monthly_expenses,
        (SELECT COUNT(*) FROM wallets WHERE user_id = p_user_id AND is_active = 1 AND deleted_at IS NULL) AS active_wallets,
        (SELECT COUNT(*) FROM goals WHERE user_id = p_user_id AND status = 'active' AND deleted_at IS NULL) AS active_goals;
END//

CREATE PROCEDURE sp_recalculate_wallet_balance(IN p_wallet_id CHAR(36))
BEGIN
    DECLARE v_balance DECIMAL(15,2);
    SELECT COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END), 0) INTO v_balance
    FROM transactions WHERE wallet_id = p_wallet_id AND deleted_at IS NULL;
    UPDATE wallets SET balance = v_balance WHERE id = p_wallet_id;
END//

CREATE TRIGGER trg_goal_completion
BEFORE UPDATE ON goals
FOR EACH ROW
BEGIN
    IF NEW.current_amount >= NEW.target_amount AND NEW.status = 'active' THEN
        SET NEW.status = 'completed';
    END IF;
END//

DELIMITER ;
