-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Янв 02 2026 г., 20:06
-- Версия сервера: 10.4.32-MariaDB
-- Версия PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `math_grinder`
--

-- --------------------------------------------------------

--
-- Структура таблицы `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_logged_in` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `last_logout` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_superadmin` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `is_logged_in`, `last_login`, `last_logout`, `created_at`, `updated_at`, `is_superadmin`) VALUES
(3, 'SVelegzhanin', '$2y$10$JZtN6R4ym4w8pGDdwi6z4OtNiUKtpb6EOrA28PFunezLDCZS6K4pi', 0, '2026-01-01 01:16:24', NULL, '2025-10-23 18:53:24', '2025-12-31 20:16:24', 1),
(6, 'AAArabaji', '$2y$10$weVzvx5KTAdzr6azz3XTBucsOV6.7YCqmT2riNIxst2oy2HM2FVmG', 0, '2025-10-25 20:29:23', NULL, '2025-10-25 16:56:48', '2025-10-25 17:29:43', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `answers`
--

CREATE TABLE `answers` (
  `id` int(11) NOT NULL,
  `participant_id` int(11) DEFAULT NULL,
  `question_id` int(11) DEFAULT NULL,
  `answer` varchar(255) NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `points` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `answer_order` int(11) DEFAULT NULL,
  `event_type` enum('grinder','quiz') DEFAULT 'grinder'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cheating_attempts`
--

CREATE TABLE `cheating_attempts` (
  `id` int(11) NOT NULL,
  `participant_id` int(11) DEFAULT NULL,
  `team` varchar(255) DEFAULT NULL,
  `question_id` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `count` int(11) DEFAULT NULL,
  `last_attempt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `current_quiz_question`
--

CREATE TABLE `current_quiz_question` (
  `id` int(11) NOT NULL,
  `quiz_question_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `phase` enum('question','answers') DEFAULT 'question',
  `question_end_time` datetime DEFAULT NULL,
  `answers_end_time` datetime DEFAULT NULL,
  `next_question_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `event_state`
--

CREATE TABLE `event_state` (
  `id` int(11) NOT NULL,
  `is_ranking_frozen` tinyint(1) DEFAULT 0,
  `event_start_time` bigint(20) DEFAULT NULL,
  `is_accepting_answers` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `event_name` varchar(255) DEFAULT 'Математическая мясорубка',
  `event_status` enum('not_started','running','finished') DEFAULT 'not_started',
  `timer_duration` int(11) DEFAULT 3600,
  `timer_remaining` int(11) DEFAULT NULL,
  `event_end_time` datetime DEFAULT NULL,
  `event_mode` enum('grinder','quiz') DEFAULT 'grinder'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `event_state`
--

INSERT INTO `event_state` (`id`, `is_ranking_frozen`, `event_start_time`, `is_accepting_answers`, `updated_at`, `event_name`, `event_status`, `timer_duration`, `timer_remaining`, `event_end_time`, `event_mode`) VALUES
(1, 0, NULL, 1, '2026-01-02 18:32:24', 'Математическая мясорубка 2026', 'finished', 3600, NULL, NULL, 'quiz');

-- --------------------------------------------------------

--
-- Структура таблицы `grinder_events`
--

CREATE TABLE `grinder_events` (
  `id` int(11) NOT NULL,
  `event_name` varchar(255) DEFAULT 'Математическая мясорубка',
  `timer_duration` int(11) DEFAULT 3600,
  `is_accepting_answers` tinyint(1) DEFAULT 1,
  `is_ranking_frozen` tinyint(1) DEFAULT 0,
  `event_status` enum('not_started','running','finished') DEFAULT 'not_started',
  `event_start_time` datetime DEFAULT NULL,
  `event_end_time` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `grinder_events`
--

INSERT INTO `grinder_events` (`id`, `event_name`, `timer_duration`, `is_accepting_answers`, `is_ranking_frozen`, `event_status`, `event_start_time`, `event_end_time`, `created_at`, `updated_at`) VALUES
(1, 'Математическая мясорубка 2026', 3600, 1, 0, 'not_started', NULL, NULL, '2025-12-27 14:22:12', '2026-01-01 16:23:15');

-- --------------------------------------------------------

--
-- Структура таблицы `participants`
--

CREATE TABLE `participants` (
  `id` int(11) NOT NULL,
  `team` varchar(255) NOT NULL,
  `score` int(11) DEFAULT 0,
  `tab_switch_count` int(11) DEFAULT 0,
  `last_tab_switch` datetime DEFAULT NULL,
  `copy_attempt_count` int(11) DEFAULT 0,
  `paste_attempt_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `event_type` enum('grinder','quiz') DEFAULT 'grinder'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `participants`
--

INSERT INTO `participants` (`id`, `team`, `score`, `tab_switch_count`, `last_tab_switch`, `copy_attempt_count`, `paste_attempt_count`, `created_at`, `updated_at`, `event_type`) VALUES
(70, '123', 4, 0, NULL, 0, 0, '2026-01-02 18:31:16', '2026-01-02 18:31:58', 'quiz');

-- --------------------------------------------------------

--
-- Структура таблицы `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `text` text NOT NULL,
  `answer` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `points` int(11) DEFAULT 1,
  `image_path` varchar(500) DEFAULT NULL,
  `has_bonus_points` tinyint(1) DEFAULT 0,
  `bonus_first_points` int(11) DEFAULT 0,
  `bonus_second_points` int(11) DEFAULT 0,
  `bonus_third_points` int(11) DEFAULT 0,
  `event_type` enum('grinder','quiz') DEFAULT 'grinder'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `questions`
--

INSERT INTO `questions` (`id`, `text`, `answer`, `created_at`, `updated_at`, `points`, `image_path`, `has_bonus_points`, `bonus_first_points`, `bonus_second_points`, `bonus_third_points`, `event_type`) VALUES
(6, 'Вопрос 2', '2', '2025-10-27 15:48:03', '2025-10-27 15:48:03', 1, NULL, 1, 0, 0, 0, 'grinder'),
(7, 'Суслик есть?', 'да', '2025-12-26 10:47:08', '2025-12-26 10:47:08', 1, NULL, 1, 0, 0, 0, 'grinder'),
(8, '2314', '1', '2025-12-28 18:31:06', '2025-12-28 18:31:06', 1, NULL, 1, 0, 0, 0, 'grinder'),
(9, '1', '2', '2025-12-28 18:31:22', '2025-12-28 18:31:22', 1, NULL, 1, 0, 0, 0, 'grinder'),
(10, '1', '3', '2025-12-28 18:31:26', '2025-12-28 18:31:26', 1, NULL, 1, 0, 0, 0, 'grinder');

-- --------------------------------------------------------

--
-- Структура таблицы `question_images`
--

CREATE TABLE `question_images` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `question_id` int(11) DEFAULT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `quiz_answers`
--

CREATE TABLE `quiz_answers` (
  `id` int(11) NOT NULL,
  `quiz_question_id` int(11) DEFAULT NULL,
  `answer_text` text NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `points` int(11) DEFAULT 0,
  `display_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `quiz_answers`
--

INSERT INTO `quiz_answers` (`id`, `quiz_question_id`, `answer_text`, `is_correct`, `points`, `display_order`) VALUES
(53, 3, '1', 0, 0, 1),
(54, 3, '2', 1, 1, 2),
(55, 3, '3', 1, 2, 3),
(56, 3, '4', 0, 0, 4),
(65, 1, '1', 0, -1, 1),
(66, 1, '2', 1, 1, 2),
(67, 1, '3', 0, 0, 3),
(68, 1, '4', 0, 0, 4);

-- --------------------------------------------------------

--
-- Структура таблицы `quiz_events`
--

CREATE TABLE `quiz_events` (
  `id` int(11) NOT NULL,
  `event_name` varchar(255) DEFAULT 'Математический квиз',
  `timer_duration` int(11) DEFAULT 1800,
  `is_accepting_answers` tinyint(1) DEFAULT 1,
  `is_ranking_frozen` tinyint(1) DEFAULT 0,
  `event_status` enum('not_started','running','finished') DEFAULT 'not_started',
  `event_start_time` datetime DEFAULT NULL,
  `event_end_time` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `quiz_events`
--

INSERT INTO `quiz_events` (`id`, `event_name`, `timer_duration`, `is_accepting_answers`, `is_ranking_frozen`, `event_status`, `event_start_time`, `event_end_time`, `created_at`, `updated_at`) VALUES
(1, 'Математический квиз 2026', 1800, 1, 0, 'not_started', NULL, NULL, '2025-12-27 14:22:12', '2026-01-02 18:26:22');

-- --------------------------------------------------------

--
-- Структура таблицы `quiz_participant_answers`
--

CREATE TABLE `quiz_participant_answers` (
  `id` int(11) NOT NULL,
  `participant_id` int(11) DEFAULT NULL,
  `quiz_question_id` int(11) DEFAULT NULL,
  `quiz_answer_id` int(11) DEFAULT NULL,
  `answered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `points_earned` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `quiz_participant_answers`
--

INSERT INTO `quiz_participant_answers` (`id`, `participant_id`, `quiz_question_id`, `quiz_answer_id`, `answered_at`, `points_earned`) VALUES
(73, 70, 1, 66, '2026-01-02 18:31:28', 1),
(74, 70, 3, 54, '2026-01-02 18:31:58', 1),
(75, 70, 3, 55, '2026-01-02 18:31:58', 2);

-- --------------------------------------------------------

--
-- Структура таблицы `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('single','multiple') DEFAULT 'single',
  `question_time` int(11) DEFAULT 30 COMMENT 'Время на вопрос в секундах',
  `answer_time` int(11) DEFAULT 10 COMMENT 'Время показа ответов в секундах',
  `display_order` int(11) DEFAULT 0,
  `image_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `quiz_event_id` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `quiz_questions`
--

INSERT INTO `quiz_questions` (`id`, `question_text`, `question_type`, `question_time`, `answer_time`, `display_order`, `image_path`, `created_at`, `updated_at`, `quiz_event_id`) VALUES
(1, 'Сколько', 'single', 20, 10, 1, '/uploads/questions/quiz_693c6d8b7a40c_68fd44e9574e7_1200x630wa_1.ico', '2025-10-27 20:00:22', '2025-12-12 19:31:23', 1),
(3, 'Много?', 'multiple', 20, 10, 2, NULL, '2025-10-27 20:37:46', '2025-10-30 20:50:58', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `quiz_session`
--

CREATE TABLE `quiz_session` (
  `id` int(11) NOT NULL,
  `current_question_id` int(11) DEFAULT NULL,
  `next_question_id` int(11) DEFAULT NULL,
  `phase` enum('question','answers','waiting') DEFAULT 'waiting',
  `question_start_time` datetime DEFAULT NULL,
  `answers_start_time` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `quiz_session`
--

INSERT INTO `quiz_session` (`id`, `current_question_id`, `next_question_id`, `phase`, `question_start_time`, `answers_start_time`, `is_active`, `created_at`, `updated_at`) VALUES
(133, NULL, NULL, 'waiting', NULL, NULL, 0, '2026-01-02 18:26:23', '2026-01-02 18:31:24'),
(134, 3, NULL, 'waiting', '2026-01-02 23:31:54', '2026-01-02 23:32:14', 0, '2026-01-02 18:31:24', '2026-01-02 18:32:24'),
(135, NULL, NULL, 'waiting', NULL, NULL, 1, '2026-01-02 18:32:24', '2026-01-02 18:32:24');

-- --------------------------------------------------------

--
-- Структура таблицы `waiting_room`
--

CREATE TABLE `waiting_room` (
  `id` int(11) NOT NULL,
  `participant_id` int(11) DEFAULT NULL,
  `display_name` varchar(100) DEFAULT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('waiting','approved','rejected') DEFAULT 'waiting',
  `rejected_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Индексы таблицы `answers`
--
ALTER TABLE `answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_answer` (`participant_id`,`question_id`),
  ADD KEY `answers_ibfk_2` (`question_id`);

--
-- Индексы таблицы `cheating_attempts`
--
ALTER TABLE `cheating_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `participant_id` (`participant_id`);

--
-- Индексы таблицы `current_quiz_question`
--
ALTER TABLE `current_quiz_question`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_question_id` (`quiz_question_id`),
  ADD KEY `idx_current_quiz_active` (`is_active`);

--
-- Индексы таблицы `event_state`
--
ALTER TABLE `event_state`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `grinder_events`
--
ALTER TABLE `grinder_events`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `participants`
--
ALTER TABLE `participants`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `question_images`
--
ALTER TABLE `question_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Индексы таблицы `quiz_answers`
--
ALTER TABLE `quiz_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_question_id` (`quiz_question_id`);

--
-- Индексы таблицы `quiz_events`
--
ALTER TABLE `quiz_events`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `quiz_participant_answers`
--
ALTER TABLE `quiz_participant_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `participant_id` (`participant_id`),
  ADD KEY `quiz_question_id` (`quiz_question_id`),
  ADD KEY `quiz_answer_id` (`quiz_answer_id`);

--
-- Индексы таблицы `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `quiz_session`
--
ALTER TABLE `quiz_session`
  ADD PRIMARY KEY (`id`),
  ADD KEY `current_question_id` (`current_question_id`),
  ADD KEY `next_question_id` (`next_question_id`);

--
-- Индексы таблицы `waiting_room`
--
ALTER TABLE `waiting_room`
  ADD PRIMARY KEY (`id`),
  ADD KEY `participant_id` (`participant_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `answers`
--
ALTER TABLE `answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT для таблицы `cheating_attempts`
--
ALTER TABLE `cheating_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=128;

--
-- AUTO_INCREMENT для таблицы `current_quiz_question`
--
ALTER TABLE `current_quiz_question`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT для таблицы `event_state`
--
ALTER TABLE `event_state`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `grinder_events`
--
ALTER TABLE `grinder_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `participants`
--
ALTER TABLE `participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT для таблицы `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `question_images`
--
ALTER TABLE `question_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `quiz_answers`
--
ALTER TABLE `quiz_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT для таблицы `quiz_events`
--
ALTER TABLE `quiz_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `quiz_participant_answers`
--
ALTER TABLE `quiz_participant_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT для таблицы `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `quiz_session`
--
ALTER TABLE `quiz_session`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=136;

--
-- AUTO_INCREMENT для таблицы `waiting_room`
--
ALTER TABLE `waiting_room`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `answers`
--
ALTER TABLE `answers`
  ADD CONSTRAINT `answers_ibfk_1` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`),
  ADD CONSTRAINT `answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `cheating_attempts`
--
ALTER TABLE `cheating_attempts`
  ADD CONSTRAINT `cheating_attempts_ibfk_1` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`);

--
-- Ограничения внешнего ключа таблицы `current_quiz_question`
--
ALTER TABLE `current_quiz_question`
  ADD CONSTRAINT `current_quiz_question_ibfk_1` FOREIGN KEY (`quiz_question_id`) REFERENCES `quiz_questions` (`id`);

--
-- Ограничения внешнего ключа таблицы `question_images`
--
ALTER TABLE `question_images`
  ADD CONSTRAINT `question_images_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `quiz_answers`
--
ALTER TABLE `quiz_answers`
  ADD CONSTRAINT `quiz_answers_ibfk_1` FOREIGN KEY (`quiz_question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `quiz_participant_answers`
--
ALTER TABLE `quiz_participant_answers`
  ADD CONSTRAINT `quiz_participant_answers_ibfk_1` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`),
  ADD CONSTRAINT `quiz_participant_answers_ibfk_2` FOREIGN KEY (`quiz_question_id`) REFERENCES `quiz_questions` (`id`),
  ADD CONSTRAINT `quiz_participant_answers_ibfk_3` FOREIGN KEY (`quiz_answer_id`) REFERENCES `quiz_answers` (`id`);

--
-- Ограничения внешнего ключа таблицы `quiz_session`
--
ALTER TABLE `quiz_session`
  ADD CONSTRAINT `quiz_session_ibfk_1` FOREIGN KEY (`current_question_id`) REFERENCES `quiz_questions` (`id`),
  ADD CONSTRAINT `quiz_session_ibfk_2` FOREIGN KEY (`next_question_id`) REFERENCES `quiz_questions` (`id`);

--
-- Ограничения внешнего ключа таблицы `waiting_room`
--
ALTER TABLE `waiting_room`
  ADD CONSTRAINT `waiting_room_ibfk_1` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
