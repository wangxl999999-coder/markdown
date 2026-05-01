-- 创建数据库
CREATE DATABASE IF NOT EXISTS `markdown_editor` 
DEFAULT CHARACTER SET utf8mb4 
DEFAULT COLLATE utf8mb4_unicode_ci;

USE `markdown_editor`;

-- 文章表
CREATE TABLE IF NOT EXISTS `articles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL DEFAULT '',
    `content` LONGTEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 图片上传记录表
CREATE TABLE IF NOT EXISTS `uploaded_images` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `original_name` VARCHAR(255) NOT NULL DEFAULT '',
    `storage_name` VARCHAR(255) NOT NULL DEFAULT '',
    `file_path` VARCHAR(500) NOT NULL DEFAULT '',
    `file_size` INT UNSIGNED NOT NULL DEFAULT 0,
    `mime_type` VARCHAR(100) NOT NULL DEFAULT '',
    `url` VARCHAR(500) NOT NULL DEFAULT '',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 微信授权记录表
CREATE TABLE IF NOT EXISTS `wechat_auth` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `openid` VARCHAR(100) NOT NULL DEFAULT '',
    `unionid` VARCHAR(100) NOT NULL DEFAULT '',
    `nickname` VARCHAR(100) NOT NULL DEFAULT '',
    `headimgurl` VARCHAR(500) NOT NULL DEFAULT '',
    `access_token` VARCHAR(500) NOT NULL DEFAULT '',
    `refresh_token` VARCHAR(500) NOT NULL DEFAULT '',
    `expires_at` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_openid` (`openid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 文章发布记录表
CREATE TABLE IF NOT EXISTS `publish_records` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `article_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `platform` VARCHAR(50) NOT NULL DEFAULT '',
    `platform_article_id` VARCHAR(255) NOT NULL DEFAULT '',
    `media_id` VARCHAR(255) NOT NULL DEFAULT '',
    `title` VARCHAR(255) NOT NULL DEFAULT '',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '0:草稿, 1:已发布',
    `publish_url` VARCHAR(500) NOT NULL DEFAULT '',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_article_id` (`article_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
