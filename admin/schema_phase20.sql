-- =========================================================================
-- Phase 20: Biometric KYC & Escrow Ledger Schema (MySQL / MariaDB)
-- =========================================================================

-- 1. KYC Verification Table
CREATE TABLE IF NOT EXISTS partner_kyc (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    shop_id INT NOT NULL,
    id_card_url TEXT NOT NULL,
    live_selfie_url TEXT NOT NULL,
    kyc_status VARCHAR(50) DEFAULT 'pending',
    verified_at DATETIME NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Escrow Transactions Ledger
CREATE TABLE IF NOT EXISTS escrow_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    buyer_id INT NOT NULL,
    seller_shop_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'BDT',
    escrow_status VARCHAR(50) DEFAULT 'held',
    release_date DATETIME NULL,
    dispute_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Note: Once KYC is verified, update the existing `partner_shops` table status:
-- UPDATE partner_shops SET is_official = 1, seller_level = 'Verified' WHERE id = [shop_id];
