-- Exécuter ce script AVANT d'importer candi_flow.sql
-- /Applications/MAMP/Library/bin/mysql80/bin/mysql -u root -proot -P 8889 -h 127.0.0.1 < database/setup.sql

CREATE DATABASE IF NOT EXISTS site_candi
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE site_candi;
