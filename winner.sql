-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 19, 2023 at 05:09 PM
-- Server version: 10.1.38-MariaDB
-- PHP Version: 7.3.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `winner`
--

-- --------------------------------------------------------

--
-- Table structure for table `coin`
--

CREATE TABLE `coin` (
  `id` bigint(20) NOT NULL,
  `short_name` varchar(5) NOT NULL,
  `full_name` text NOT NULL,
  `last_buy_price` float NOT NULL,
  `last_sell_price` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `coin_1_hour`
--

CREATE TABLE `coin_1_hour` (
  `coin_symbol` varchar(32) NOT NULL,
  `high` float NOT NULL,
  `low` float NOT NULL,
  `open` float NOT NULL,
  `close` float NOT NULL,
  `openTime` timestamp NULL DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `coin_prices`
--

CREATE TABLE `coin_prices` (
  `coin_symbol` varchar(32) NOT NULL,
  `bid_price` float NOT NULL COMMENT 'the highest price a buyer is willing to pay',
  `ask_price` float NOT NULL COMMENT 'the minimum price a seller is willing to accept',
  `price_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `price_date_minute` varchar(12) DEFAULT NULL COMMENT 'Keep only one price with same values per minute to avoid cluttering'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `coin_price_segments`
--

CREATE TABLE `coin_price_segments` (
  `coin_symbol` varchar(32) NOT NULL,
  `high` float NOT NULL,
  `low` float NOT NULL,
  `open` float NOT NULL,
  `close` float NOT NULL,
  `open_time` timestamp NULL DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Stand-in structure for view `minmax1hour`
-- (See below for the actual view)
--
CREATE TABLE `minmax1hour` (
`coin_symbol` varchar(32)
,`max_price_1_hour` float
,`min_price_1_hour` float
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `minmax4hour`
-- (See below for the actual view)
--
CREATE TABLE `minmax4hour` (
`coin_symbol` varchar(32)
,`max_in_4_hour` float
,`min_in_4_hour` float
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `minmax8hour`
-- (See below for the actual view)
--
CREATE TABLE `minmax8hour` (
`coin_symbol` varchar(32)
,`max_in_8_hour` float
,`min_in_8_hour` float
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `minmax24hour`
-- (See below for the actual view)
--
CREATE TABLE `minmax24hour` (
`coin_symbol` varchar(32)
,`max_in_24_hour` float
,`min_in_24_hour` float
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `minmax36hour`
-- (See below for the actual view)
--
CREATE TABLE `minmax36hour` (
`coin_symbol` varchar(32)
,`max_in_36_hour` float
,`min_in_36_hour` float
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `minmax48hour`
-- (See below for the actual view)
--
CREATE TABLE `minmax48hour` (
`coin_symbol` varchar(32)
,`max_in_48_hour` float
,`min_in_48_hour` float
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `price_chart`
-- (See below for the actual view)
--
CREATE TABLE `price_chart` (
`coin_symbol` varchar(32)
,`bid_price` float
,`ask_price` float
,`price_date` timestamp
,`max_in_24_hour` float
,`min_in_24_hour` float
,`buy_price_diff_max_24_hour` double
,`sell_price_diff_max_24_hour` double
,`max_in_36_hour` float
,`min_in_36_hour` float
,`buy_price_diff_max_36_hour` double
,`sell_price_diff_max_36_hour` double
,`max_in_48_hour` float
,`min_in_48_hour` float
,`buy_price_diff_max_48_hour` double
,`sell_price_diff_max_48_hour` double
);

-- --------------------------------------------------------

--
-- Structure for view `minmax1hour`
--
DROP TABLE IF EXISTS `minmax1hour`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `minmax1hour`  AS  select `cp`.`coin_symbol` AS `coin_symbol`,max(`cp`.`bid_price`) AS `max_price_1_hour`,min(`cp`.`bid_price`) AS `min_price_1_hour` from `coin_prices` `cp` where (`cp`.`price_date` > (now() - interval 1 hour)) group by `cp`.`coin_symbol` ;

-- --------------------------------------------------------

--
-- Structure for view `minmax4hour`
--
DROP TABLE IF EXISTS `minmax4hour`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `minmax4hour`  AS  select `c`.`coin_symbol` AS `coin_symbol`,max(`c`.`high`) AS `max_in_4_hour`,min(`c`.`low`) AS `min_in_4_hour` from `coin_1_hour` `c` where (`c`.`openTime` > (now() - interval 4 hour)) group by `c`.`coin_symbol` ;

-- --------------------------------------------------------

--
-- Structure for view `minmax8hour`
--
DROP TABLE IF EXISTS `minmax8hour`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `minmax8hour`  AS  select `c`.`coin_symbol` AS `coin_symbol`,max(`c`.`high`) AS `max_in_8_hour`,min(`c`.`low`) AS `min_in_8_hour` from `coin_1_hour` `c` where (`c`.`openTime` >= (now() - interval 8 hour)) group by `c`.`coin_symbol` ;

-- --------------------------------------------------------

--
-- Structure for view `minmax24hour`
--
DROP TABLE IF EXISTS `minmax24hour`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `minmax24hour`  AS  select `c`.`coin_symbol` AS `coin_symbol`,max(`c`.`high`) AS `max_in_24_hour`,min(`c`.`low`) AS `min_in_24_hour` from `coin_1_hour` `c` where ((`c`.`openTime` < (now() - interval 8 hour)) and (`c`.`openTime` >= (now() - interval 24 hour))) group by `c`.`coin_symbol` ;

-- --------------------------------------------------------

--
-- Structure for view `minmax36hour`
--
DROP TABLE IF EXISTS `minmax36hour`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `minmax36hour`  AS  select `c`.`coin_symbol` AS `coin_symbol`,max(`c`.`high`) AS `max_in_36_hour`,min(`c`.`low`) AS `min_in_36_hour` from `coin_1_hour` `c` where ((`c`.`openTime` < (now() - interval 24 hour)) and (`c`.`openTime` >= (now() - interval 36 hour))) group by `c`.`coin_symbol` ;

-- --------------------------------------------------------

--
-- Structure for view `minmax48hour`
--
DROP TABLE IF EXISTS `minmax48hour`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `minmax48hour`  AS  select `c`.`coin_symbol` AS `coin_symbol`,max(`c`.`high`) AS `max_in_48_hour`,min(`c`.`low`) AS `min_in_48_hour` from `coin_1_hour` `c` where ((`c`.`openTime` < (now() - interval 36 hour)) and (`c`.`openTime` >= (now() - interval 48 hour))) group by `c`.`coin_symbol` ;

-- --------------------------------------------------------

--
-- Structure for view `price_chart`
--
DROP TABLE IF EXISTS `price_chart`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `price_chart`  AS  select `cp`.`coin_symbol` AS `coin_symbol`,`cp`.`bid_price` AS `bid_price`,`cp`.`ask_price` AS `ask_price`,`cp`.`price_date` AS `price_date`,`mm24`.`max_in_24_hour` AS `max_in_24_hour`,`mm24`.`min_in_24_hour` AS `min_in_24_hour`,if((`cp`.`ask_price` < `mm24`.`max_in_24_hour`),(((-(1) * (`mm24`.`max_in_24_hour` - `cp`.`ask_price`)) / `mm24`.`max_in_24_hour`) * 100),(((`mm24`.`max_in_24_hour` - `cp`.`ask_price`) / `mm24`.`max_in_24_hour`) * 100)) AS `buy_price_diff_max_24_hour`,if((`cp`.`bid_price` < `mm24`.`max_in_24_hour`),(((-(1) * (`mm24`.`max_in_24_hour` - `cp`.`bid_price`)) / `mm24`.`max_in_24_hour`) * 100),(((`mm24`.`max_in_24_hour` - `cp`.`bid_price`) / `mm24`.`max_in_24_hour`) * 100)) AS `sell_price_diff_max_24_hour`,`mm36`.`max_in_36_hour` AS `max_in_36_hour`,`mm36`.`min_in_36_hour` AS `min_in_36_hour`,if((`cp`.`ask_price` < `mm36`.`max_in_36_hour`),(((-(1) * (`mm36`.`max_in_36_hour` - `cp`.`ask_price`)) / `mm36`.`max_in_36_hour`) * 100),(((`mm36`.`max_in_36_hour` - `cp`.`ask_price`) / `mm36`.`max_in_36_hour`) * 100)) AS `buy_price_diff_max_36_hour`,if((`cp`.`bid_price` < `mm36`.`max_in_36_hour`),(((-(1) * (`mm36`.`max_in_36_hour` - `cp`.`bid_price`)) / `mm36`.`max_in_36_hour`) * 100),(((`mm36`.`max_in_36_hour` - `cp`.`bid_price`) / `mm36`.`max_in_36_hour`) * 100)) AS `sell_price_diff_max_36_hour`,`mm48`.`max_in_48_hour` AS `max_in_48_hour`,`mm48`.`min_in_48_hour` AS `min_in_48_hour`,if((`cp`.`ask_price` < `mm48`.`max_in_48_hour`),(((-(1) * (`mm48`.`max_in_48_hour` - `cp`.`ask_price`)) / `mm48`.`max_in_48_hour`) * 100),(((`mm48`.`max_in_48_hour` - `cp`.`ask_price`) / `mm48`.`max_in_48_hour`) * 100)) AS `buy_price_diff_max_48_hour`,if((`cp`.`bid_price` < `mm48`.`max_in_48_hour`),(((-(1) * (`mm48`.`max_in_48_hour` - `cp`.`bid_price`)) / `mm48`.`max_in_48_hour`) * 100),(((`mm48`.`max_in_48_hour` - `cp`.`bid_price`) / `mm48`.`max_in_48_hour`) * 100)) AS `sell_price_diff_max_48_hour` from (((`coin_prices` `cp` left join `minmax24hour` `mm24` on((`mm24`.`coin_symbol` = `cp`.`coin_symbol`))) left join `minmax36hour` `mm36` on((`mm36`.`coin_symbol` = `cp`.`coin_symbol`))) left join `minmax48hour` `mm48` on((`mm48`.`coin_symbol` = `cp`.`coin_symbol`))) where (`mm24`.`max_in_24_hour` is not null) order by `cp`.`coin_symbol` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `coin`
--
ALTER TABLE `coin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coin_1_hour`
--
ALTER TABLE `coin_1_hour`
  ADD UNIQUE KEY `one_per_hour` (`coin_symbol`,`openTime`) USING BTREE,
  ADD KEY `coin_symbol` (`coin_symbol`);

--
-- Indexes for table `coin_prices`
--
ALTER TABLE `coin_prices`
  ADD UNIQUE KEY `same_prices_per_minute` (`coin_symbol`,`bid_price`,`ask_price`,`price_date_minute`) COMMENT 'Allow only one same price per minute',
  ADD KEY `coin_symbol` (`coin_symbol`);

--
-- Indexes for table `coin_price_segments`
--
ALTER TABLE `coin_price_segments`
  ADD UNIQUE KEY `one_per_segment` (`coin_symbol`,`open_time`) USING BTREE,
  ADD KEY `coin_symbol` (`coin_symbol`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `coin`
--
ALTER TABLE `coin`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
