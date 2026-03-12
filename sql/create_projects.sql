-- Run this in phpMyAdmin or MySQL CLI to add the projects table
CREATE TABLE IF NOT EXISTS `projects` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT NOT NULL,
    `title`       VARCHAR(200) NOT NULL,
    `description` TEXT,
    `tech_stack`  VARCHAR(500),
    `project_url` VARCHAR(500),
    `github_url`  VARCHAR(500),
    `image`       VARCHAR(500),
    `sort_order`  INT DEFAULT 0,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
