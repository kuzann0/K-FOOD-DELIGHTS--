ALTER TABLE categories ADD COLUMN active TINYINT(1) DEFAULT 1;
UPDATE categories SET active = 1;