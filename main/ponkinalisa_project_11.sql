-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Хост: localhost:3306
-- Время создания: Дек 18 2025 г., 16:43
-- Версия сервера: 5.7.24
-- Версия PHP: 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `ponkinalisa_project_11`
--

-- --------------------------------------------------------

--
-- Структура таблицы `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `text` text NOT NULL,
  `answer` int(11) NOT NULL,
  `path_to_img` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `tasks`
--

INSERT INTO `tasks` (`id`, `test_id`, `type_id`, `text`, `answer`, `path_to_img`) VALUES
(84, 38, 73, '2 + 2 = ?', 4, ''),
(85, 38, 74, '3 - 4 = ?', -1, ''),
(86, 38, 75, '2 * 0.5 = ', 1, ''),
(87, 35, 76, 'Проектор полностью освещает экран A высотой 80 см, расположенный на расстоянии 250 см от проектора. На каком наименьшем расстоянии (в сантиметрах) от проектора нужно расположить экран B высотой 160 см, чтобы он был полностью освещен, если настройки проектора остаются неизменными?', 500, ''),
(88, 35, 76, 'Человек ростом 1,7 м стоит на расстоянии 8 шагов от столба, на котором висит фонарь. Тень человека равна четырем шагам. На какой высоте (в метрах) расположен фонарь?', 5, ''),
(89, 35, 76, 'На каком расстоянии (в метрах) от фонаря стоит человек ростом 2 м, если длина его тени равна 1 м, высота фонаря 9 м?', 4, ''),
(90, 35, 77, 'При проведении опыта вещество равномерно охлаждали в течение 10 минут. При этом каждую минуту температура вещества уменьшалась на  Найдите температуру вещества (в градусах Цельсия) через 6 минут после начала проведения опыта, если его начальная температура составляла ', -60, ''),
(91, 35, 77, 'Каучуковый мячик с силой бросили на асфальт. Отскочив, мячик подпрыгнул на 5,4 м, а при каждом следующем прыжке он поднимался на высоту в три раза меньше предыдущей. При каком по счету прыжке мячик в первый раз не достигнет высоты 10 см?', 5, ''),
(92, 35, 77, 'В ходе распада радиоактивного изотопа его масса уменьшается вдвое каждые 6 минут. В начальный момент масса изотопа составляла 640 мг. Найдите массу изотопа через 42 минуты. Ответ дайте в миллиграммах.', 5, ''),
(93, 35, 77, 'В амфитеатре 14 рядов. В первом ряду 20 мест, а в каждом следующем на 3 места больше, чем в предыдущем. Сколько мест в десятом ряду амфитеатра?', 47, ''),
(94, 35, 78, ' Хорды AC и BD окружности пересекаются в точке P, \r\nBP=4, CP=12, DP=21. Найдите AP. ', 7, '../user_img/eaponkina/4449369316.png'),
(95, 35, 78, 'Четырёхугольник ABCD вписан в окружность. Прямые AB и CD пересекаются в точке K, BK=6, DK=10, BC=12. Найдите AD.', 63, '../user_img/eaponkina/5815140714.png'),
(96, 35, 78, 'Прямая, параллельная стороне AC треугольника ABC, пересекает стороны AB и BC в точках M и N соответственно, AB=66, AC=44, MN=24. Найдите AM.', 9, '../user_img/eaponkina/2713238584.png'),
(97, 35, 78, 'Найдите тангенс угла AOB, изображённого\r\nна рисунке.', 20, '../user_img/eaponkina/7211303318.png'),
(98, 35, 78, 'В треугольнике ABC угол C равен 90°, AC=24, AB=25. Найдите sinB.', 9, '../user_img/eaponkina/4197992674.png'),
(103, 40, 83, 'сколько щупалец у осьминога?', 8, ''),
(104, 40, 83, 'сколько миллиардов людей на планете?', 8, ''),
(105, 40, 84, 'сколько хромосом у здорового человека?', 46, ''),
(106, 40, 84, 'сколько звезд в созвездии малой медведицы?', 25, '');

-- --------------------------------------------------------

--
-- Структура таблицы `tests`
--

CREATE TABLE `tests` (
  `id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `name` text NOT NULL,
  `description` text NOT NULL,
  `time` int(11) NOT NULL,
  `grade5` int(11) NOT NULL,
  `grade4` int(11) NOT NULL,
  `grade3` int(11) NOT NULL,
  `count_tasks` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `tests`
--

INSERT INTO `tests` (`id`, `author_id`, `name`, `description`, `time`, `grade5`, `grade4`, `grade3`, `count_tasks`, `is_active`) VALUES
(35, 2, 'математика', 'тест для проверки знаний учащихся по математике уровня средней школы', 40, 80, 60, 40, 3, 1),
(38, 2, 'ознакомление', 'тест для демонстраций работы системы', 2, 75, 60, 35, 3, 1),
(40, 2, 'тест для демонстрации', 'данный тест будет показан на защите проектной работы в целях ознакомления с функциональностью системы', 10, 85, 65, 45, 2, 1),
(41, 2, 'Тест для удаления', 'здесь ничего', 5, 85, 65, 45, 1, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `test_results`
--

CREATE TABLE `test_results` (
  `student_id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `score` int(11) DEFAULT NULL,
  `mark` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `variant` json NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `test_results`
--

INSERT INTO `test_results` (`student_id`, `test_id`, `score`, `mark`, `date`, `variant`) VALUES
(2, 38, 0, 2, '2025-12-18 23:04:23', '[{\"id\": 84, \"text\": \"2 + 2 = ?\", \"answer\": 4, \"test_id\": 38, \"type_id\": 73, \"path_to_img\": \"\"}, {\"id\": 86, \"text\": \"2 * 0.5 = \", \"answer\": 1, \"test_id\": 38, \"type_id\": 75, \"path_to_img\": \"\"}, {\"id\": 85, \"text\": \"3 - 4 = ?\", \"answer\": -1, \"test_id\": 38, \"type_id\": 74, \"path_to_img\": \"\"}]'),
(2, 35, 1, 2, '2025-12-18 23:27:48', '[{\"id\": 89, \"text\": \"На каком расстоянии (в метрах) от фонаря стоит человек ростом 2 м, если длина его тени равна 1 м, высота фонаря 9 м?\", \"answer\": 4, \"test_id\": 35, \"type_id\": 76, \"path_to_img\": \"\"}, {\"id\": 92, \"text\": \"В ходе распада радиоактивного изотопа его масса уменьшается вдвое каждые 6 минут. В начальный момент масса изотопа составляла 640 мг. Найдите массу изотопа через 42 минуты. Ответ дайте в миллиграммах.\", \"answer\": 5, \"test_id\": 35, \"type_id\": 77, \"path_to_img\": \"\"}, {\"id\": 94, \"text\": \" Хорды AC и BD окружности пересекаются в точке P, \\r\\nBP=4, CP=12, DP=21. Найдите AP. \", \"answer\": 7, \"test_id\": 35, \"type_id\": 78, \"path_to_img\": \"../user_img/eaponkina/4449369316.png\"}]'),
(2, 40, 1, 3, '2025-12-18 23:29:40', '[{\"id\": 104, \"text\": \"сколько миллиардов людей на планете?\", \"answer\": 8, \"test_id\": 40, \"type_id\": 83, \"path_to_img\": \"\"}, {\"id\": 106, \"text\": \"сколько звезд в созвездии малой медведицы?\", \"answer\": 25, \"test_id\": 40, \"type_id\": 84, \"path_to_img\": \"\"}]');

-- --------------------------------------------------------

--
-- Структура таблицы `types`
--

CREATE TABLE `types` (
  `id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `amount` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `types`
--

INSERT INTO `types` (`id`, `test_id`, `score`, `amount`) VALUES
(73, 38, 1, 1),
(74, 38, 1, 1),
(75, 38, 1, 1),
(76, 35, 1, 1),
(77, 35, 1, 1),
(78, 35, 1, 1),
(83, 40, 1, 1),
(84, 40, 1, 1),
(89, 41, 1, 1);

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `surname` text NOT NULL,
  `status` enum('teacher','student','admin') NOT NULL,
  `patronymic` text NOT NULL,
  `login` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `name`, `surname`, `status`, `patronymic`, `login`) VALUES
(2, 'Елизавета', 'Понькина', 'admin', 'Андреевна', 'eaponkina');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `test_id` (`test_id`),
  ADD KEY `type_id` (`type_id`);

--
-- Индексы таблицы `tests`
--
ALTER TABLE `tests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `author_id` (`author_id`);

--
-- Индексы таблицы `test_results`
--
ALTER TABLE `test_results`
  ADD KEY `student_id` (`student_id`),
  ADD KEY `test_id` (`test_id`);

--
-- Индексы таблицы `types`
--
ALTER TABLE `types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `test_id` (`test_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT для таблицы `tests`
--
ALTER TABLE `tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT для таблицы `types`
--
ALTER TABLE `types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`),
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`type_id`) REFERENCES `types` (`id`);

--
-- Ограничения внешнего ключа таблицы `tests`
--
ALTER TABLE `tests`
  ADD CONSTRAINT `tests_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `test_results`
--
ALTER TABLE `test_results`
  ADD CONSTRAINT `test_results_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `test_results_ibfk_2` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`);

--
-- Ограничения внешнего ключа таблицы `types`
--
ALTER TABLE `types`
  ADD CONSTRAINT `types_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
