-- Vocabulary Bank Database Schema
-- Creates tables for managing vocabulary questions and choices

-- Create vocabulary_questions table
CREATE TABLE IF NOT EXISTS `vocabulary_questions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `word` VARCHAR(255) NOT NULL,
  `definition` TEXT NOT NULL,
  `example_sentence` TEXT,
  `difficulty` INT NOT NULL DEFAULT 1 COMMENT '1-5 difficulty level',
  `grade_level` VARCHAR(50) NOT NULL COMMENT 'Target grade: 7, 8, 9, 10',
  `created_by` INT NOT NULL COMMENT 'User ID of creator',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` BOOLEAN DEFAULT TRUE,
  INDEX `idx_grade_level` (`grade_level`),
  INDEX `idx_difficulty` (`difficulty`),
  INDEX `idx_is_active` (`is_active`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create vocabulary_choices table
CREATE TABLE IF NOT EXISTS `vocabulary_choices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `question_id` INT NOT NULL,
  `choice_text` VARCHAR(255) NOT NULL,
  `is_correct` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`question_id`) REFERENCES `vocabulary_questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample vocabulary questions from existing data
INSERT INTO `vocabulary_questions` (`word`, `definition`, `example_sentence`, `difficulty`, `grade_level`, `created_by`, `is_active`) VALUES
('abundant', 'existing in large quantities; plentiful', 'The forest has abundant wildlife and natural resources.', 1, '7', 1, TRUE),
('analyze', 'examine in detail to understand structure or meaning', 'Let''s analyze the data to find patterns and trends.', 1, '7', 1, TRUE),
('ancient', 'belonging to the very distant past; very old', 'The ancient temple was built over a thousand years ago.', 1, '7', 1, TRUE),
('benevolent', 'well meaning and kindly; charitable', 'The benevolent teacher helped all students succeed.', 2, '8', 1, TRUE),
('comprehensive', 'complete; including everything or nearly everything', 'This is a comprehensive guide to learning English.', 2, '8', 1, TRUE);

-- Insert sample choices for the first question (abundant)
INSERT INTO `vocabulary_choices` (`question_id`, `choice_text`, `is_correct`) VALUES
(1, 'plentiful', TRUE),
(1, 'scarce', FALSE),
(1, 'limited', FALSE),
(1, 'rare', FALSE);

-- Insert sample choices for the second question (analyze)
INSERT INTO `vocabulary_choices` (`question_id`, `choice_text`, `is_correct`) VALUES
(2, 'examine', TRUE),
(2, 'ignore', FALSE),
(2, 'overlook', FALSE),
(2, 'neglect', FALSE);

-- Insert sample choices for the third question (ancient)
INSERT INTO `vocabulary_choices` (`question_id`, `choice_text`, `is_correct`) VALUES
(3, 'very old', TRUE),
(3, 'modern', FALSE),
(3, 'new', FALSE),
(3, 'contemporary', FALSE);

-- Insert sample choices for the fourth question (benevolent)
INSERT INTO `vocabulary_choices` (`question_id`, `choice_text`, `is_correct`) VALUES
(4, 'kind and generous', TRUE),
(4, 'cruel', FALSE),
(4, 'harsh', FALSE),
(4, 'mean', FALSE);

-- Insert sample choices for the fifth question (comprehensive)
INSERT INTO `vocabulary_choices` (`question_id`, `choice_text`, `is_correct`) VALUES
(5, 'complete', TRUE),
(5, 'incomplete', FALSE),
(5, 'partial', FALSE),
(5, 'limited', FALSE);
