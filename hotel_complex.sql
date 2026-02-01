-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Хост: MySQL-8.0
-- Время создания: Окт 02 2025 г., 16:03
-- Версия сервера: 8.0.41
-- Версия PHP: 8.2.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `hotel_complex`
--

DELIMITER $$
--
-- Процедуры
--
CREATE DEFINER=`root`@`%` PROCEDURE `create_booking` (IN `p_user_id` INT, IN `p_room_id` INT, IN `p_check_in` DATE, IN `p_check_out` DATE, IN `p_guests` INT, IN `p_requests` TEXT)   BEGIN
    DECLARE v_nights INT;
    DECLARE v_price_per_night DECIMAL(10,2);
    DECLARE v_total_price DECIMAL(10,2);
    
    SET v_nights = DATEDIFF(p_check_out, p_check_in);
    
    SELECT rt.price_per_night INTO v_price_per_night
    FROM rooms r
    JOIN room_types rt ON r.room_type_id = rt.id
    WHERE r.id = p_room_id;
    
    SET v_total_price = v_nights * v_price_per_night;
    
    INSERT INTO bookings (user_id, room_id, check_in_date, check_out_date, guests_count, total_price, special_requests)
    VALUES (p_user_id, p_room_id, p_check_in, p_check_out, p_guests, v_total_price, p_requests);
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `get_available_rooms` (IN `p_check_in` DATE, IN `p_check_out` DATE, IN `p_guests` INT)   BEGIN
    SELECT r.*, rt.name as room_type_name, rt.description, rt.price_per_night, 
           rt.capacity, rt.amenities, rt.image_url
    FROM rooms r
    JOIN room_types rt ON r.room_type_id = rt.id
    WHERE r.status = 'available' 
    AND rt.capacity >= p_guests
    AND r.id NOT IN (
        SELECT room_id 
        FROM bookings 
        WHERE status IN ('confirmed', 'pending')
        AND (
            (check_in_date BETWEEN p_check_in AND p_check_out) OR
            (check_out_date BETWEEN p_check_in AND p_check_out) OR
            (p_check_in BETWEEN check_in_date AND check_out_date) OR
            (p_check_out BETWEEN check_in_date AND check_out_date)
        )
    )
    ORDER BY rt.price_per_night;
END$$

--
-- Функции
--
CREATE DEFINER=`root`@`%` FUNCTION `is_room_available` (`p_room_id` INT, `p_check_in` DATE, `p_check_out` DATE) RETURNS TINYINT(1) DETERMINISTIC BEGIN
    DECLARE v_count INT;
    
    SELECT COUNT(*) INTO v_count
    FROM bookings 
    WHERE room_id = p_room_id 
    AND status IN ('confirmed', 'pending')
    AND (
        (check_in_date BETWEEN p_check_in AND p_check_out) OR
        (check_out_date BETWEEN p_check_in AND p_check_out) OR
        (p_check_in BETWEEN check_in_date AND check_out_date)
    );
    
    RETURN v_count = 0;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `bookings`
--

CREATE TABLE `bookings` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `room_id` int NOT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `guests_count` int NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','cancelled','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `special_requests` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `room_id`, `check_in_date`, `check_out_date`, `guests_count`, `total_price`, `status`, `special_requests`, `created_at`) VALUES
(3, 4, 1, '2025-09-21', '2025-09-22', 2, 2500.00, 'completed', '', '2025-09-21 14:59:28'),
(4, 4, 3, '2025-09-21', '2025-09-22', 2, 2500.00, 'completed', '', '2025-09-21 14:59:34'),
(5, 4, 6, '2025-09-21', '2025-09-22', 2, 10000.00, 'completed', '', '2025-09-21 14:59:42'),
(7, 4, 8, '2025-09-21', '2025-09-22', 2, 5000.00, 'completed', '', '2025-09-21 15:37:44'),
(8, 4, 7, '2025-09-21', '2025-09-22', 2, 10000.00, 'completed', '', '2025-09-21 15:54:52'),
(9, 5, 4, '2025-09-21', '2025-09-22', 2, 5000.00, 'completed', '', '2025-09-21 15:56:32'),
(10, 4, 2, '2025-09-21', '2025-09-22', 2, 2500.00, 'completed', '', '2025-09-21 16:05:49'),
(11, 4, 3, '2025-09-21', '2025-12-10', 2, 200000.00, 'completed', '', '2025-09-21 16:06:02'),
(12, 4, 7, '2025-09-21', '2025-09-22', 2, 10000.00, 'completed', '', '2025-09-21 16:38:55'),
(17, 4, 2, '2025-10-01', '2025-10-02', 2, 2500.00, 'confirmed', '', '2025-10-01 10:52:46'),
(18, 4, 1, '2025-10-01', '2025-10-02', 2, 2500.00, 'pending', '', '2025-10-01 12:53:17');

-- --------------------------------------------------------

--
-- Дублирующая структура для представления `booking_details`
-- (См. Ниже фактическое представление)
--
CREATE TABLE `booking_details` (
`check_in_date` date
,`check_out_date` date
,`created_at` timestamp
,`email` varchar(100)
,`first_name` varchar(50)
,`guests_count` int
,`id` int
,`last_name` varchar(50)
,`price_per_night` decimal(10,2)
,`room_id` int
,`room_number` varchar(10)
,`room_type_name` varchar(100)
,`special_requests` text
,`status` enum('pending','confirmed','cancelled','completed')
,`total_price` decimal(10,2)
,`user_id` int
,`username` varchar(50)
);

-- --------------------------------------------------------

--
-- Структура таблицы `rooms`
--

CREATE TABLE `rooms` (
  `id` int NOT NULL,
  `room_number` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `room_type_id` int NOT NULL,
  `floor` int NOT NULL,
  `status` enum('available','occupied','maintenance','cleaning') COLLATE utf8mb4_unicode_ci DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `room_type_id`, `floor`, `status`) VALUES
(1, '101', 1, 1, 'occupied'),
(2, '102', 1, 1, 'available'),
(3, '103', 1, 1, 'available'),
(4, '201', 2, 2, 'available'),
(5, '202', 2, 2, 'available'),
(6, '301', 3, 3, 'available'),
(7, '302', 3, 3, 'available'),
(8, '111', 2, 1, 'available'),
(9, '123', 2, 2, 'available'),
(10, '456', 3, 2, 'available');

-- --------------------------------------------------------

--
-- Структура таблицы `room_types`
--

CREATE TABLE `room_types` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price_per_night` decimal(10,2) NOT NULL,
  `capacity` int NOT NULL,
  `amenities` text COLLATE utf8mb4_unicode_ci,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `room_types`
--

INSERT INTO `room_types` (`id`, `name`, `description`, `price_per_night`, `capacity`, `amenities`, `image_url`) VALUES
(1, 'Стандартный', 'Комфортабельный номер с всеми необходимыми удобствами', 2500.00, 2, 'Wi-Fi, TV, Кондиционер, Сейф', NULL),
(2, 'Люкс', 'Просторный номер с улучшенной отделкой и дополнительными услугами', 5000.00, 3, 'Wi-Fi, TV, Мини-бар, Гидромассажная ванна', NULL),
(3, 'Президентский', 'Эксклюзивный номер высшего класса', 10000.00, 4, 'Все включено, Персональный дворецкий, Отдельная терраса', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `services`
--

CREATE TABLE `services` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(10,2) NOT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT '1',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `services`
--

INSERT INTO `services` (`id`, `name`, `description`, `price`, `category`, `is_available`, `status`, `created_at`) VALUES
(1, 'Завтрак', 'Континентальный завтрак', 1500.00, 'Питание', 1, 'active', '2025-10-01 10:39:20'),
(2, 'Ужин', 'Трехразовое питание', 1500.00, 'Питание', 1, 'active', '2025-10-01 10:39:20'),
(3, 'Спа', 'Спа-процедуры', 3000.00, 'Услуги', 1, 'active', '2025-10-01 10:39:20'),
(4, 'Трансфер', 'Трансфер из/в аэропорт', 2000.00, 'Транспорт', 1, 'active', '2025-10-01 10:39:20'),
(5, 'Прачечная', 'Стирка и глажка белья', 800.00, 'Услуги', 1, 'active', '2025-10-01 10:39:20'),
(6, 'Экскурсия', 'Обзорная экскурсия по городу', 2500.00, 'Развлечения', 1, 'active', '2025-10-01 10:39:20'),
(7, 'Завтрак', 'Континентальный завтрак с выбором блюд', 500.00, 'Питание', 1, 'active', '2025-10-01 10:39:20'),
(8, 'Ужин', 'Трехразовое питание по меню ресторана', 1500.00, 'Питание', 1, 'active', '2025-10-01 10:39:20'),
(9, 'Спа-процедуры', 'Комплекс спа-процедур с массажем', 3000.00, 'Услуги', 1, 'active', '2025-10-01 10:39:20'),
(10, 'Трансфер', 'Трансфер из/в аэропорт', 2000.00, 'Транспорт', 1, 'active', '2025-10-01 10:39:20'),
(12, 'Экскурсия', 'Обзорная экскурсия по городу', 2500.00, 'Развлечения', 1, 'active', '2025-10-01 10:39:20');

-- --------------------------------------------------------

--
-- Структура таблицы `service_orders`
--

CREATE TABLE `service_orders` (
  `id` int NOT NULL,
  `booking_id` int NOT NULL,
  `service_id` int NOT NULL,
  `quantity` int DEFAULT '1',
  `order_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('requested','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'requested'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `service_rooms`
--

CREATE TABLE `service_rooms` (
  `id` int NOT NULL,
  `service_id` int NOT NULL,
  `room_id` int NOT NULL,
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `service_rooms`
--

INSERT INTO `service_rooms` (`id`, `service_id`, `room_id`, `assigned_at`) VALUES
(5, 1, 1, '2025-10-01 11:52:44'),
(6, 1, 2, '2025-10-01 11:52:44'),
(7, 1, 3, '2025-10-01 11:52:44');

-- --------------------------------------------------------

--
-- Структура таблицы `service_staff`
--

CREATE TABLE `service_staff` (
  `id` int NOT NULL,
  `service_id` int NOT NULL,
  `staff_id` int NOT NULL,
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `service_staff`
--

INSERT INTO `service_staff` (`id`, `service_id`, `staff_id`, `assigned_at`) VALUES
(4, 1, 2, '2025-10-01 11:52:44'),
(5, 1, 3, '2025-10-01 11:52:44');

-- --------------------------------------------------------

--
-- Структура таблицы `staff`
--

CREATE TABLE `staff` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `staff`
--

INSERT INTO `staff` (`id`, `name`, `position`, `email`, `phone`, `department`, `status`, `created_at`) VALUES
(2, 'Петрова Мария Александровна', 'Горничная', 'diksan2004@mail.ru', '+79184374481', NULL, 'active', '2025-10-01 11:12:22'),
(3, 'Петрова Мария Валерьевна', 'Горничная', 'diksan2004@mail.ru', '+79184374481', NULL, 'active', '2025-10-01 11:19:29');

-- --------------------------------------------------------

--
-- Структура таблицы `staff_rooms`
--

CREATE TABLE `staff_rooms` (
  `id` int NOT NULL,
  `staff_id` int DEFAULT NULL,
  `room_id` int DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `staff_rooms`
--

INSERT INTO `staff_rooms` (`id`, `staff_id`, `room_id`, `assigned_at`) VALUES
(5, 2, 1, '2025-10-01 11:12:22'),
(6, 3, 1, '2025-10-01 11:19:34');

-- --------------------------------------------------------

--
-- Структура таблицы `staff_tasks`
--

CREATE TABLE `staff_tasks` (
  `id` int NOT NULL,
  `booking_id` int DEFAULT NULL,
  `room_number` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `task_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `assigned_to` int DEFAULT NULL,
  `status` enum('pending','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `due_date` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('guest','staff','admin') COLLATE utf8mb4_unicode_ci DEFAULT 'guest',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `first_name`, `last_name`, `phone`, `role`, `created_at`, `is_active`) VALUES
(1, 'admin', 'admin@hotel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Администратор', 'Системы', NULL, 'admin', '2025-09-21 12:28:52', 0),
(2, 'staff1', 'staff@hotel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Иван', 'Персоналов', NULL, 'staff', '2025-09-21 12:28:52', 1),
(3, 'guest1', 'guest@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Петр', 'Гостев', NULL, 'guest', '2025-09-21 12:28:52', 1),
(4, 'blessed_six', 'blessedtriplesix@mail.ru', '$2y$10$/XfUGfZca1zHrNdouUmoj.DqNpv95QbPFerELjPOVpZXIVceyxYLa', 'Дмитрий', 'Дикий', '89966480802', 'admin', '2025-09-21 13:03:08', 1),
(5, 'qwexiq', 'diksan2004@mail.ru', '$2y$10$7FhzOTi1rrHkjq9qPrr.d.qLbGdyYqyOqeGdEEijDbgPQBo0a9UP6', 'Дмитрий', 'пупкин', '89966480802', 'guest', '2025-09-21 15:56:13', 1),
(6, 'Diego_Henessy', 'asd@asd.ru', '$2y$10$7WJMBN36HDqgut3VaFwW/evTdg04vedBeCPy4azUQ2ePnc/VBrcjm', 'Алексей', 'Домашний', '89964071113', 'guest', '2025-09-27 12:24:27', 1),
(7, 'aaasss', 'dddd@gmail.com', '$2y$10$bAjvlbhjA6zGWhVKPN4ysu0hDK3wBE2UDq6VCceoHR7qI/.w02nJu', 'фывs', 'фывs', '89991212122', 'staff', '2025-10-01 11:02:37', 1),
(8, 'aaassss', 'dddds@gmail.com', '$2y$10$uelm7QUcwnD6aWHrnasJxujxQ1MVRkFfLFZ4bJTg/Sg2aKD2TYcc.', 'фывs', 'фывs', '89991212122', 'staff', '2025-10-01 15:28:32', 1);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Индексы таблицы `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`),
  ADD KEY `room_type_id` (`room_type_id`);

--
-- Индексы таблицы `room_types`
--
ALTER TABLE `room_types`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `service_orders`
--
ALTER TABLE `service_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Индексы таблицы `service_rooms`
--
ALTER TABLE `service_rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_service_room` (`service_id`,`room_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Индексы таблицы `service_staff`
--
ALTER TABLE `service_staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_service_staff` (`service_id`,`staff_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Индексы таблицы `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `staff_rooms`
--
ALTER TABLE `staff_rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Индексы таблицы `staff_tasks`
--
ALTER TABLE `staff_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `created_by` (`created_by`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT для таблицы `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `room_types`
--
ALTER TABLE `room_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `services`
--
ALTER TABLE `services`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT для таблицы `service_orders`
--
ALTER TABLE `service_orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `service_rooms`
--
ALTER TABLE `service_rooms`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `service_staff`
--
ALTER TABLE `service_staff`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `staff_rooms`
--
ALTER TABLE `staff_rooms`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `staff_tasks`
--
ALTER TABLE `staff_tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

-- --------------------------------------------------------

--
-- Структура для представления `booking_details`
--
DROP TABLE IF EXISTS `booking_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `booking_details`  AS SELECT `b`.`id` AS `id`, `b`.`user_id` AS `user_id`, `b`.`room_id` AS `room_id`, `b`.`check_in_date` AS `check_in_date`, `b`.`check_out_date` AS `check_out_date`, `b`.`guests_count` AS `guests_count`, `b`.`total_price` AS `total_price`, `b`.`status` AS `status`, `b`.`special_requests` AS `special_requests`, `b`.`created_at` AS `created_at`, `u`.`username` AS `username`, `u`.`email` AS `email`, `u`.`first_name` AS `first_name`, `u`.`last_name` AS `last_name`, `r`.`room_number` AS `room_number`, `rt`.`name` AS `room_type_name`, `rt`.`price_per_night` AS `price_per_night` FROM (((`bookings` `b` join `users` `u` on((`b`.`user_id` = `u`.`id`))) join `rooms` `r` on((`b`.`room_id` = `r`.`id`))) join `room_types` `rt` on((`r`.`room_type_id` = `rt`.`id`))) ;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `service_orders`
--
ALTER TABLE `service_orders`
  ADD CONSTRAINT `service_orders_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_orders_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `service_rooms`
--
ALTER TABLE `service_rooms`
  ADD CONSTRAINT `service_rooms_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_rooms_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `service_staff`
--
ALTER TABLE `service_staff`
  ADD CONSTRAINT `service_staff_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_staff_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `staff_rooms`
--
ALTER TABLE `staff_rooms`
  ADD CONSTRAINT `staff_rooms_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_rooms_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `staff_tasks`
--
ALTER TABLE `staff_tasks`
  ADD CONSTRAINT `staff_tasks_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`),
  ADD CONSTRAINT `staff_tasks_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `staff_tasks_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
