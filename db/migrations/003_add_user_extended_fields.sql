ALTER TABLE users
  ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS last_name VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS email VARCHAR(191) NULL,
  ADD COLUMN IF NOT EXISTS birthday DATE NULL,
  ADD COLUMN IF NOT EXISTS gender VARCHAR(20) NULL,
  ADD COLUMN IF NOT EXISTS vendor_id_path VARCHAR(255) NULL;

-- Create unique index on email (optional but recommended)
CREATE UNIQUE INDEX idx_users_email_unique ON users (email);

-- Verify changes
-- DESCRIBE users;
