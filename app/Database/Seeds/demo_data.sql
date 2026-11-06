-- InmoGestión 2026 - demo / test data (single source for CodeIgniter and MySQL Workbench)
--
-- WHAT IT LOADS: 9 users (1 admin, 3 owners, 4 tenants, 1 deactivated), 8 properties
-- (one soft-deleted), 6 contracts (4 in force, 1 finished, 1 rescinded), 24 installments in
-- every state, 19 payments (approved, pending and rejected), 15 settlements (12 transferred,
-- 3 pending) and 7 notifications. All emails end in @example.com.
-- Dates are relative to the day it is loaded (installments of the last 6 months, a contract
-- expiring in 45 days, overdue installments with their late fee), so it never goes stale.
-- Payment receipts (payment_receipts) are not loaded: they need real files in
-- writable/uploads/comprobantes/.
--
-- DEVELOPMENT ONLY. Never load it in production.
--
-- HOW TO LOAD IT
--   CodeIgniter : php spark db:seed DemoSeeder
--                 (password of all demo users = seed.demoPassword in .env; refuses to run in production)
--   Workbench   : select your schema (double click it, or add a USE statement above), then open
--                 this file and run it (lightning bolt). The schema must already exist: run
--                 `php spark migrate` first. Loading it twice fails on the unique keys.
--
-- LOGGING IN: in Workbench the demo users get an unusable password hash, so nobody can log in
-- with them by accident. To log in, either load the data with the CodeIgniter seeder, or set
-- your own hash before the first statement below, for example:
--   SET @demo_password_hash = '<output of: php -r "echo password_hash('my-password', PASSWORD_BCRYPT);">';
--
-- TO RELOAD FROM SCRATCH in Workbench, first run these statements. They delete ALL the data of
-- the database, not only the demo rows. (In CodeIgniter use `php spark migrate:refresh` instead.)
--   SET FOREIGN_KEY_CHECKS = 0;
--   TRUNCATE TABLE notifications;
--   TRUNCATE TABLE settlement_installments;
--   TRUNCATE TABLE settlements;
--   TRUNCATE TABLE payment_receipts;
--   TRUNCATE TABLE payments;
--   TRUNCATE TABLE installments;
--   TRUNCATE TABLE contracts;
--   TRUNCATE TABLE properties;
--   TRUNCATE TABLE password_resets;
--   TRUNCATE TABLE users;
--   TRUNCATE TABLE roles;
--   SET FOREIGN_KEY_CHECKS = 1;
--
-- FORMAT RULES (the CodeIgniter seeder splits this file by itself): one statement ends with a
-- semicolon at the end of a line, comments are whole lines starting with two dashes, and no
-- text literal contains a semicolon.

SET @demo_password_hash = COALESCE(@demo_password_hash, '$2y$10$xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');

-- First day of the current month and of the six previous ones.
SET @m0 = DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE()) - 1 DAY);
SET @m1 = DATE_SUB(@m0, INTERVAL 1 MONTH);
SET @m2 = DATE_SUB(@m0, INTERVAL 2 MONTH);
SET @m3 = DATE_SUB(@m0, INTERVAL 3 MONTH);
SET @m4 = DATE_SUB(@m0, INTERVAL 4 MONTH);
SET @m5 = DATE_SUB(@m0, INTERVAL 5 MONTH);
SET @m6 = DATE_SUB(@m0, INTERVAL 6 MONTH);

-- Roles (the CodeIgniter RoleSeeder creates the same ones; IGNORE keeps this safe to repeat).
INSERT IGNORE INTO roles (name, created_at, updated_at) VALUES
('admin', NOW(), NOW()),
('owner', NOW(), NOW()),
('tenant', NOW(), NOW());

SET @role_admin = (SELECT id FROM roles WHERE name = 'admin');
SET @role_owner = (SELECT id FROM roles WHERE name = 'owner');
SET @role_tenant = (SELECT id FROM roles WHERE name = 'tenant');

-- Users
INSERT INTO users (role_id, first_name, last_name, document_number, email, phone, address, password_hash, created_at, updated_at, deleted_at) VALUES
(@role_admin, 'Carla', 'Rossi', '27000001', 'admin.demo@example.com', '2991000001', 'Oficina central 100, Cipolletti', @demo_password_hash, NOW(), NOW(), NULL),
(@role_owner, 'María', 'Gómez', '20111222', 'owner1.demo@example.com', '2991000011', 'Av. Roca 1500, Cipolletti', @demo_password_hash, NOW(), NOW(), NULL),
(@role_owner, 'Jorge', 'Sosa', '20222333', 'owner2.demo@example.com', '2991000012', 'Mitre 320, Neuquén', @demo_password_hash, NOW(), NOW(), NULL),
(@role_owner, 'Inversiones del Sur SA', 'Empresa', '30711223344', 'owner3.demo@example.com', '2991000013', 'Av. Argentina 900, Neuquén', @demo_password_hash, NOW(), NOW(), NULL),
(@role_tenant, 'Lucas', 'Pérez', '30333444', 'tenant1.demo@example.com', '2991000021', 'Av. Siempre Viva 742, Cipolletti', @demo_password_hash, NOW(), NOW(), NULL),
(@role_tenant, 'Sofía', 'Díaz', '31444555', 'tenant2.demo@example.com', '2991000022', 'Los Pinos 120, Neuquén', @demo_password_hash, NOW(), NOW(), NULL),
(@role_tenant, 'Martín', 'López', '32555666', 'tenant3.demo@example.com', '2991000023', 'Belgrano 1010 4B, Neuquén', @demo_password_hash, NOW(), NOW(), NULL),
(@role_tenant, 'Camila', 'Torres', '33666777', 'tenant4.demo@example.com', '2991000024', 'Mitre 88 Piso 2, Neuquén', @demo_password_hash, NOW(), NOW(), NULL),
(@role_tenant, 'Ana', 'Vega', '29888999', 'former.tenant.demo@example.com', '2991000025', 'Alberdi 305, Cipolletti', @demo_password_hash, DATE_SUB(NOW(), INTERVAL 400 DAY), NOW(), DATE_SUB(NOW(), INTERVAL 60 DAY));

SET @admin = (SELECT id FROM users WHERE email = 'admin.demo@example.com');
SET @owner1 = (SELECT id FROM users WHERE email = 'owner1.demo@example.com');
SET @owner2 = (SELECT id FROM users WHERE email = 'owner2.demo@example.com');
SET @owner3 = (SELECT id FROM users WHERE email = 'owner3.demo@example.com');
SET @tenant1 = (SELECT id FROM users WHERE email = 'tenant1.demo@example.com');
SET @tenant2 = (SELECT id FROM users WHERE email = 'tenant2.demo@example.com');
SET @tenant3 = (SELECT id FROM users WHERE email = 'tenant3.demo@example.com');
SET @tenant4 = (SELECT id FROM users WHERE email = 'tenant4.demo@example.com');
SET @former = (SELECT id FROM users WHERE email = 'former.tenant.demo@example.com');

-- Properties (one at a time so LAST_INSERT_ID() is reliable)
INSERT INTO properties (owner_id, address, city, type, rooms, surface, included_services, base_rent, description, status, created_at, updated_at)
VALUES (@owner1, 'Av. Siempre Viva 742', 'Cipolletti', 'apartment', 3, 68.50, 'Agua, luz y gas', 250000.00, 'Departamento luminoso en el centro, con balcón.', 'rented', NOW(), NOW());
SET @p1 = LAST_INSERT_ID();

INSERT INTO properties (owner_id, address, city, type, rooms, surface, included_services, base_rent, description, status, created_at, updated_at)
VALUES (@owner1, 'Los Pinos 120', 'Neuquén', 'house', 4, 120.00, 'Agua, luz y gas', 380000.00, 'Casa con jardín y cochera.', 'rented', NOW(), NOW());
SET @p2 = LAST_INSERT_ID();

INSERT INTO properties (owner_id, address, city, type, rooms, surface, included_services, base_rent, description, status, created_at, updated_at)
VALUES (@owner2, 'San Martín 450', 'Cipolletti', 'commercial', 2, 45.00, NULL, 300000.00, 'Local sobre calle principal, en refacción.', 'maintenance', NOW(), NOW());
SET @p3 = LAST_INSERT_ID();

INSERT INTO properties (owner_id, address, city, type, rooms, surface, included_services, base_rent, description, status, created_at, updated_at)
VALUES (@owner2, 'Belgrano 1010 4B', 'Neuquén', 'apartment', 2, 52.00, 'Agua y luz', 210000.00, NULL, 'rented', NOW(), NOW());
SET @p4 = LAST_INSERT_ID();

INSERT INTO properties (owner_id, address, city, type, rooms, surface, included_services, base_rent, description, status, created_at, updated_at)
VALUES (@owner3, 'Mitre 88 Piso 2', 'Neuquén', 'office', 3, 80.00, 'Agua, luz e internet', 420000.00, 'Oficina con tres ambientes y recepción.', 'rented', NOW(), NOW());
SET @p5 = LAST_INSERT_ID();

INSERT INTO properties (owner_id, address, city, type, rooms, surface, included_services, base_rent, description, status, created_at, updated_at)
VALUES (@owner3, 'Ruta 22 km 1210', 'Cipolletti', 'land', NULL, 600.00, NULL, 150000.00, 'Terreno con acceso directo a la ruta.', 'available', NOW(), NOW());
SET @p6 = LAST_INSERT_ID();

INSERT INTO properties (owner_id, address, city, type, rooms, surface, included_services, base_rent, description, status, created_at, updated_at)
VALUES (@owner1, 'Alberdi 305', 'Cipolletti', 'house', 3, 95.00, 'Agua, luz y gas', 330000.00, 'Casa a estrenar tras el último contrato.', 'available', NOW(), NOW());
SET @p7 = LAST_INSERT_ID();

-- Deactivated property (soft delete) with the employee who removed it.
INSERT INTO properties (owner_id, address, city, type, rooms, surface, included_services, base_rent, description, status, created_at, updated_at, deleted_at, deleted_by)
VALUES (@owner2, 'Roca 77', 'Neuquén', 'apartment', 1, 30.00, NULL, 160000.00, 'Dado de baja por venta del inmueble.', 'available', NOW(), NOW(), DATE_SUB(NOW(), INTERVAL 30 DAY), @admin);

-- Contracts (columns: property, tenant, owner, start, end, rent, update %, due day, commission %, late fee daily %, status, closing data)
INSERT INTO contracts (property_id, tenant_id, owner_id, start_date, end_date, monthly_rent, update_percentage, due_day, commission_percentage, late_fee_daily_percentage, status, closed_at, closing_reason, created_at, updated_at)
VALUES (@p1, @tenant1, @owner1, @m3, DATE_SUB(DATE_ADD(@m3, INTERVAL 24 MONTH), INTERVAL 1 DAY), 250000.00, 10.00, 10, 10.00, 0.50, 'in_force', NULL, NULL, NOW(), NOW());
SET @c1 = LAST_INSERT_ID();

-- Expires in 45 days: useful for the contract expiry alerts (HU-24).
INSERT INTO contracts (property_id, tenant_id, owner_id, start_date, end_date, monthly_rent, update_percentage, due_day, commission_percentage, late_fee_daily_percentage, status, closed_at, closing_reason, created_at, updated_at)
VALUES (@p2, @tenant2, @owner1, @m6, DATE_ADD(CURDATE(), INTERVAL 45 DAY), 380000.00, 12.00, 5, 8.00, 0.40, 'in_force', NULL, NULL, NOW(), NOW());
SET @c2 = LAST_INSERT_ID();

INSERT INTO contracts (property_id, tenant_id, owner_id, start_date, end_date, monthly_rent, update_percentage, due_day, commission_percentage, late_fee_daily_percentage, status, closed_at, closing_reason, created_at, updated_at)
VALUES (@p4, @tenant3, @owner2, @m2, DATE_SUB(DATE_ADD(@m2, INTERVAL 24 MONTH), INTERVAL 1 DAY), 210000.00, 8.00, 15, 10.00, 0.50, 'in_force', NULL, NULL, NOW(), NOW());
SET @c3 = LAST_INSERT_ID();

INSERT INTO contracts (property_id, tenant_id, owner_id, start_date, end_date, monthly_rent, update_percentage, due_day, commission_percentage, late_fee_daily_percentage, status, closed_at, closing_reason, created_at, updated_at)
VALUES (@p5, @tenant4, @owner3, @m4, DATE_SUB(DATE_ADD(@m4, INTERVAL 36 MONTH), INTERVAL 1 DAY), 420000.00, 15.00, 1, 12.00, 0.50, 'in_force', NULL, NULL, NOW(), NOW());
SET @c4 = LAST_INSERT_ID();

-- Finished contract with a tenant who was later deactivated.
INSERT INTO contracts (property_id, tenant_id, owner_id, start_date, end_date, monthly_rent, update_percentage, due_day, commission_percentage, late_fee_daily_percentage, status, closed_at, closing_reason, created_at, updated_at)
VALUES (@p7, @former, @owner1, @m5, DATE_SUB(@m2, INTERVAL 1 DAY), 330000.00, 10.00, 10, 10.00, 0.50, 'finished', DATE_SUB(@m2, INTERVAL 1 DAY), 'Finalización por vencimiento del plazo', NOW(), NOW());
SET @c5 = LAST_INSERT_ID();

INSERT INTO contracts (property_id, tenant_id, owner_id, start_date, end_date, monthly_rent, update_percentage, due_day, commission_percentage, late_fee_daily_percentage, status, closed_at, closing_reason, created_at, updated_at)
VALUES (@p3, @tenant1, @owner2, @m5, DATE_SUB(DATE_ADD(@m5, INTERVAL 12 MONTH), INTERVAL 1 DAY), 300000.00, 0.00, 20, 10.00, 0.50, 'rescinded', DATE_ADD(@m3, INTERVAL 14 DAY), 'Rescisión anticipada a pedido del inquilino', NOW(), NOW());
SET @c6 = LAST_INSERT_ID();

-- Installments (columns: contract, number, period, due date, base amount, late fee, status)
-- Contract 1: due day 10
INSERT INTO installments (contract_id, number, period, due_date, base_amount, late_fee, status, created_at, updated_at) VALUES
(@c1, 1, @m3, DATE_ADD(@m3, INTERVAL 9 DAY), 250000.00, 0.00, 'paid', NOW(), NOW()),
(@c1, 2, @m2, DATE_ADD(@m2, INTERVAL 9 DAY), 250000.00, 0.00, 'paid', NOW(), NOW()),
(@c1, 3, @m1, DATE_ADD(@m1, INTERVAL 9 DAY), 250000.00, 0.00, 'under_review', NOW(), NOW()),
(@c1, 4, @m0, DATE_ADD(@m0, INTERVAL 9 DAY), 250000.00, 0.00, 'pending', NOW(), NOW());

-- Contract 2: due day 5, installment 6 is overdue with its late fee (0.40 % per day)
INSERT INTO installments (contract_id, number, period, due_date, base_amount, late_fee, status, created_at, updated_at) VALUES
(@c2, 1, @m6, DATE_ADD(@m6, INTERVAL 4 DAY), 380000.00, 0.00, 'paid', NOW(), NOW()),
(@c2, 2, @m5, DATE_ADD(@m5, INTERVAL 4 DAY), 380000.00, 0.00, 'paid', NOW(), NOW()),
(@c2, 3, @m4, DATE_ADD(@m4, INTERVAL 4 DAY), 380000.00, 0.00, 'paid', NOW(), NOW()),
(@c2, 4, @m3, DATE_ADD(@m3, INTERVAL 4 DAY), 380000.00, 0.00, 'paid', NOW(), NOW()),
(@c2, 5, @m2, DATE_ADD(@m2, INTERVAL 4 DAY), 380000.00, 0.00, 'paid', NOW(), NOW()),
(@c2, 6, @m1, DATE_ADD(@m1, INTERVAL 4 DAY), 380000.00, ROUND(380000.00 * 0.40 / 100 * DATEDIFF(CURDATE(), DATE_ADD(@m1, INTERVAL 4 DAY)), 2), 'overdue', NOW(), NOW()),
(@c2, 7, @m0, DATE_ADD(@m0, INTERVAL 4 DAY), 380000.00, 0.00, 'pending', NOW(), NOW());

-- Contract 3: due day 15
INSERT INTO installments (contract_id, number, period, due_date, base_amount, late_fee, status, created_at, updated_at) VALUES
(@c3, 1, @m2, DATE_ADD(@m2, INTERVAL 14 DAY), 210000.00, 0.00, 'paid', NOW(), NOW()),
(@c3, 2, @m1, DATE_ADD(@m1, INTERVAL 14 DAY), 210000.00, 0.00, 'paid', NOW(), NOW()),
(@c3, 3, @m0, DATE_ADD(@m0, INTERVAL 14 DAY), 210000.00, 0.00, 'pending', NOW(), NOW());

-- Contract 4: due day 1, two overdue installments (0.50 % per day)
INSERT INTO installments (contract_id, number, period, due_date, base_amount, late_fee, status, created_at, updated_at) VALUES
(@c4, 1, @m4, @m4, 420000.00, 0.00, 'paid', NOW(), NOW()),
(@c4, 2, @m3, @m3, 420000.00, 0.00, 'paid', NOW(), NOW()),
(@c4, 3, @m2, @m2, 420000.00, ROUND(420000.00 * 0.50 / 100 * DATEDIFF(CURDATE(), @m2), 2), 'overdue', NOW(), NOW()),
(@c4, 4, @m1, @m1, 420000.00, ROUND(420000.00 * 0.50 / 100 * DATEDIFF(CURDATE(), @m1), 2), 'overdue', NOW(), NOW()),
(@c4, 5, @m0, @m0, 420000.00, 0.00, 'pending', NOW(), NOW());

-- Contract 5 (finished): due day 10
INSERT INTO installments (contract_id, number, period, due_date, base_amount, late_fee, status, created_at, updated_at) VALUES
(@c5, 1, @m5, DATE_ADD(@m5, INTERVAL 9 DAY), 330000.00, 0.00, 'paid', NOW(), NOW()),
(@c5, 2, @m4, DATE_ADD(@m4, INTERVAL 9 DAY), 330000.00, 0.00, 'paid', NOW(), NOW()),
(@c5, 3, @m3, DATE_ADD(@m3, INTERVAL 9 DAY), 330000.00, 0.00, 'paid', NOW(), NOW());

-- Contract 6 (rescinded): due day 20
INSERT INTO installments (contract_id, number, period, due_date, base_amount, late_fee, status, created_at, updated_at) VALUES
(@c6, 1, @m5, DATE_ADD(@m5, INTERVAL 19 DAY), 300000.00, 0.00, 'paid', NOW(), NOW()),
(@c6, 2, @m4, DATE_ADD(@m4, INTERVAL 19 DAY), 300000.00, 0.00, 'paid', NOW(), NOW());

-- Payments: every paid installment has one approved payment, resolved by the admin the day after.
INSERT INTO payments (installment_id, amount, paid_on, method, reference_number, status, rejection_reason, resolved_by, resolved_at, created_at, updated_at)
SELECT i.id, i.base_amount, i.due_date, ELT(1 + MOD(i.id, 3), 'bank_transfer', 'deposit', 'digital_gateway'), CONCAT('DEMO-', LPAD(i.id, 4, '0')), 'approved', NULL, @admin, TIMESTAMP(DATE_ADD(i.due_date, INTERVAL 1 DAY), '10:00:00'), NOW(), NOW()
FROM installments i
WHERE i.status = 'paid' AND i.contract_id IN (@c1, @c2, @c3, @c4, @c5, @c6);

-- Waiting for the admin: contract 1, installment 3 (status under_review).
INSERT INTO payments (installment_id, amount, paid_on, method, reference_number, status, rejection_reason, resolved_by, resolved_at, created_at, updated_at)
SELECT i.id, i.base_amount, DATE_ADD(i.due_date, INTERVAL 2 DAY), 'bank_transfer', 'DEMO-PENDING-1', 'pending_validation', NULL, NULL, NULL, NOW(), NOW()
FROM installments i
WHERE i.contract_id = @c1 AND i.number = 3;
SET @pay_pending = LAST_INSERT_ID();

-- Rejected attempt that was later fixed: contract 2, installment 5 also has an approved payment.
INSERT INTO payments (installment_id, amount, paid_on, method, reference_number, status, rejection_reason, resolved_by, resolved_at, created_at, updated_at)
SELECT i.id, i.base_amount, i.due_date, 'deposit', 'DEMO-REJECTED-1', 'rejected', 'Comprobante ilegible', @admin, TIMESTAMP(i.due_date, '09:00:00'), NOW(), NOW()
FROM installments i
WHERE i.contract_id = @c2 AND i.number = 5;
SET @pay_rejected1 = LAST_INSERT_ID();

-- Rejected and still unpaid: contract 4, installment 3 stays overdue.
INSERT INTO payments (installment_id, amount, paid_on, method, reference_number, status, rejection_reason, resolved_by, resolved_at, created_at, updated_at)
SELECT i.id, i.base_amount, DATE_ADD(i.due_date, INTERVAL 5 DAY), 'bank_transfer', 'DEMO-REJECTED-2', 'rejected', 'Transferencia no acreditada', @admin, TIMESTAMP(DATE_ADD(i.due_date, INTERVAL 6 DAY), '09:00:00'), NOW(), NOW()
FROM installments i
WHERE i.contract_id = @c4 AND i.number = 3;
SET @pay_rejected2 = LAST_INSERT_ID();

-- Settlements: paid installments up to three months ago are already transferred to the owner,
-- the ones from two months ago are pending, and last month's are not settled yet.
INSERT INTO settlements (owner_id, contract_id, period, gross_amount, commission_percentage, commission_amount, net_amount, status, transferred_at, operation_number, created_at, updated_at)
SELECT c.owner_id, c.id, i.period, i.base_amount, c.commission_percentage,
       ROUND(i.base_amount * c.commission_percentage / 100, 2),
       i.base_amount - ROUND(i.base_amount * c.commission_percentage / 100, 2),
       IF(i.period <= @m3, 'transferred', 'pending_transfer'),
       IF(i.period <= @m3, DATE_ADD(DATE_ADD(i.period, INTERVAL 1 MONTH), INTERVAL 19 DAY), NULL),
       IF(i.period <= @m3, CONCAT('OP-', DATE_FORMAT(i.period, '%Y%m'), '-', LPAD(c.id, 4, '0')), NULL),
       NOW(), NOW()
FROM installments i
JOIN contracts c ON c.id = i.contract_id
WHERE i.status = 'paid' AND i.period <= @m2 AND c.id IN (@c1, @c2, @c3, @c4, @c5, @c6);

INSERT INTO settlement_installments (settlement_id, installment_id, created_at, updated_at)
SELECT s.id, i.id, NOW(), NOW()
FROM settlements s
JOIN installments i ON i.contract_id = s.contract_id AND i.period = s.period
WHERE s.contract_id IN (@c1, @c2, @c3, @c4, @c5, @c6);

-- Notifications (some unread, some read)
INSERT INTO notifications (user_id, type, message, reference_type, reference_id, read_at, created_at, updated_at) VALUES
(@admin, 'payment_submitted', 'Lucas Pérez informó el pago de la cuota 3 de Av. Siempre Viva 742.', 'payment', @pay_pending, NULL, NOW(), NOW()),
(@admin, 'contract_expiring', 'El contrato de Los Pinos 120 vence en 45 días.', 'contract', @c2, NULL, NOW(), NOW()),
(@owner1, 'contract_expiring', 'El contrato de Los Pinos 120 vence en 45 días.', 'contract', @c2, NULL, NOW(), NOW()),
(@tenant1, 'installment_due_soon', 'La cuota 4 de Av. Siempre Viva 742 está por vencer.', 'installment', (SELECT id FROM installments WHERE contract_id = @c1 AND number = 4), NULL, NOW(), NOW()),
(@tenant2, 'payment_rejected', 'Tu pago de la cuota 5 fue rechazado: Comprobante ilegible. Volvé a enviar el comprobante.', 'payment', @pay_rejected1, DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 21 DAY), NOW()),
(@tenant4, 'payment_rejected', 'Tu pago de la cuota 3 fue rechazado: Transferencia no acreditada. Volvé a enviar el comprobante.', 'payment', @pay_rejected2, NULL, NOW(), NOW()),
(@tenant3, 'payment_approved', 'Tu pago de la cuota 2 fue aprobado.', 'payment', (SELECT p.id FROM payments p JOIN installments i ON i.id = p.installment_id WHERE i.contract_id = @c3 AND i.number = 2 AND p.status = 'approved'), DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY), NOW());
