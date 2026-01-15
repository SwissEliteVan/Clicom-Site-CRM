-- CLICOM CRM schema
-- MySQL 8.x

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  role ENUM('admin','manager','staff') NOT NULL DEFAULT 'staff',
  failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE clients (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_name VARCHAR(190) NULL,
  contact_name VARCHAR(190) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(50) NULL,
  status ENUM('lead','active','inactive') NOT NULL DEFAULT 'lead',
  source VARCHAR(100) NULL,
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_clients_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  description TEXT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE quotes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id INT UNSIGNED NOT NULL,
  reference VARCHAR(50) NOT NULL,
  status ENUM('draft','sent','accepted','rejected','expired') NOT NULL DEFAULT 'draft',
  amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  valid_until DATE NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE invoices (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id INT UNSIGNED NOT NULL,
  reference VARCHAR(50) NOT NULL,
  status ENUM('draft','sent','partial','paid','overdue') NOT NULL DEFAULT 'draft',
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  tax_rate DECIMAL(5,2) NOT NULL DEFAULT 7.70,
  tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  issued_at DATE NULL,
  due_at DATE NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  UNIQUE KEY uq_invoices_reference (reference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE invoice_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NULL,
  description VARCHAR(255) NOT NULL,
  quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  line_total DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  method ENUM('bank_transfer','card','cash','twint') NOT NULL DEFAULT 'bank_transfer',
  paid_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE projects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id INT UNSIGNED NOT NULL,
  name VARCHAR(190) NOT NULL,
  status ENUM('planned','active','paused','completed') NOT NULL DEFAULT 'planned',
  starts_on DATE NULL,
  ends_on DATE NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tasks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NULL,
  client_id INT UNSIGNED NULL,
  title VARCHAR(190) NOT NULL,
  description TEXT NULL,
  status ENUM('todo','in_progress','done') NOT NULL DEFAULT 'todo',
  priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
  due_at DATE NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE files (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id INT UNSIGNED NULL,
  project_id INT UNSIGNED NULL,
  filename VARCHAR(255) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  size_bytes BIGINT UNSIGNED NOT NULL,
  uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE portal_tokens (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  UNIQUE KEY uq_portal_tokens_hash (token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE automation_rules (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  trigger_event VARCHAR(100) NOT NULL,
  action_type VARCHAR(100) NOT NULL,
  action_payload JSON NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE activity_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  ip_address VARCHAR(45) NOT NULL,
  action VARCHAR(190) NOT NULL,
  context JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_activity_user (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DELIMITER $$

CREATE TRIGGER trg_invoice_items_before_insert
BEFORE INSERT ON invoice_items
FOR EACH ROW
BEGIN
  SET NEW.line_total = NEW.quantity * NEW.unit_price;
END$$

CREATE TRIGGER trg_invoice_items_before_update
BEFORE UPDATE ON invoice_items
FOR EACH ROW
BEGIN
  SET NEW.line_total = NEW.quantity * NEW.unit_price;
END$$

CREATE TRIGGER trg_invoice_items_after_insert
AFTER INSERT ON invoice_items
FOR EACH ROW
BEGIN
  UPDATE invoices
  SET subtotal = (
      SELECT COALESCE(SUM(line_total), 0) FROM invoice_items WHERE invoice_id = NEW.invoice_id
    ),
    tax_amount = ROUND((
      SELECT COALESCE(SUM(line_total), 0) FROM invoice_items WHERE invoice_id = NEW.invoice_id
    ) * (tax_rate / 100), 2),
    total = subtotal + tax_amount
  WHERE id = NEW.invoice_id;
END$$

CREATE TRIGGER trg_invoice_items_after_update
AFTER UPDATE ON invoice_items
FOR EACH ROW
BEGIN
  UPDATE invoices
  SET subtotal = (
      SELECT COALESCE(SUM(line_total), 0) FROM invoice_items WHERE invoice_id = NEW.invoice_id
    ),
    tax_amount = ROUND((
      SELECT COALESCE(SUM(line_total), 0) FROM invoice_items WHERE invoice_id = NEW.invoice_id
    ) * (tax_rate / 100), 2),
    total = subtotal + tax_amount
  WHERE id = NEW.invoice_id;
END$$

CREATE TRIGGER trg_invoice_items_after_delete
AFTER DELETE ON invoice_items
FOR EACH ROW
BEGIN
  UPDATE invoices
  SET subtotal = (
      SELECT COALESCE(SUM(line_total), 0) FROM invoice_items WHERE invoice_id = OLD.invoice_id
    ),
    tax_amount = ROUND((
      SELECT COALESCE(SUM(line_total), 0) FROM invoice_items WHERE invoice_id = OLD.invoice_id
    ) * (tax_rate / 100), 2),
    total = subtotal + tax_amount
  WHERE id = OLD.invoice_id;
END$$

CREATE TRIGGER trg_payments_after_insert
AFTER INSERT ON payments
FOR EACH ROW
BEGIN
  DECLARE paid_total DECIMAL(12,2);
  DECLARE invoice_total DECIMAL(12,2);

  SELECT COALESCE(SUM(amount), 0) INTO paid_total FROM payments WHERE invoice_id = NEW.invoice_id;
  SELECT total INTO invoice_total FROM invoices WHERE id = NEW.invoice_id;

  UPDATE invoices
  SET status = CASE
    WHEN paid_total >= invoice_total THEN 'paid'
    WHEN paid_total > 0 THEN 'partial'
    ELSE status
  END
  WHERE id = NEW.invoice_id;
END$$

DELIMITER ;

CREATE VIEW view_overdue_invoices AS
SELECT
  invoices.id,
  invoices.reference,
  clients.company_name,
  clients.contact_name,
  invoices.total,
  invoices.due_at,
  DATEDIFF(CURDATE(), invoices.due_at) AS days_overdue
FROM invoices
JOIN clients ON clients.id = invoices.client_id
WHERE invoices.status IN ('sent', 'partial', 'overdue')
  AND invoices.due_at IS NOT NULL
  AND invoices.due_at < CURDATE();

CREATE VIEW view_monthly_revenue AS
SELECT
  DATE_FORMAT(paid_at, '%Y-%m') AS revenue_month,
  SUM(amount) AS revenue_total
FROM payments
GROUP BY DATE_FORMAT(paid_at, '%Y-%m')
ORDER BY revenue_month DESC;

INSERT INTO users (email, password_hash, first_name, last_name, role)
VALUES (
  'admin@clicom.ch',
  '$2y$12$9jsbhY97fW5f8YPjzVoLE.0jHbeO568sg64Ur59EOEmEFoyoIfSqy',
  'Admin',
  'CLICOM',
  'admin'
);
