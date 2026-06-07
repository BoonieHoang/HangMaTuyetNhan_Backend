-- =============================================
-- Script hoàn tiền: Thêm cột vào bảng payments
-- Chạy trực tiếp trên Railway MySQL console
-- =============================================

-- 1. Mở rộng enum status để hỗ trợ refund_pending và refunded
ALTER TABLE payments 
MODIFY COLUMN status ENUM('pending','paid','failed','refund_pending','refunded') NOT NULL DEFAULT 'pending';

-- 2. Thêm cột lý do yêu cầu hoàn tiền
ALTER TABLE payments 
ADD COLUMN refund_reason VARCHAR(500) NULL AFTER paid_at;

-- 3. Thêm ghi chú của admin khi xử lý hoàn tiền
ALTER TABLE payments 
ADD COLUMN refund_note VARCHAR(500) NULL AFTER refund_reason;

-- 4. Thêm thời điểm hoàn tiền hoàn tất
ALTER TABLE payments 
ADD COLUMN refunded_at TIMESTAMP NULL AFTER refund_note;

-- 5. Ghi vào bảng migrations
INSERT INTO migrations (migration, batch) 
VALUES ('2026_06_07_000001_add_refund_to_payments_table', (SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations m2));

-- Kiểm tra kết quả
DESCRIBE payments;
