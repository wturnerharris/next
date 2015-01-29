# ************************************************************
# Sequel Pro SQL dump
# Version 4096
#
# http://www.sequelpro.com/
# http://code.google.com/p/sequel-pro/
#
# Host: 127.0.0.1 (MySQL 5.6.21)
# Database: mta_data
# Generation Time: 2015-01-29 13:18:34 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table agency
# ------------------------------------------------------------

DROP TABLE IF EXISTS `agency`;

CREATE TABLE `agency` (
  `agency_id` varchar(255) DEFAULT NULL,
  `agency_name` varchar(255) DEFAULT NULL,
  `agency_url` varchar(255) DEFAULT NULL,
  `agency_timezone` varchar(255) DEFAULT NULL,
  `agency_lang` varchar(255) DEFAULT NULL,
  `agency_phone` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table calendar
# ------------------------------------------------------------

DROP TABLE IF EXISTS `calendar`;

CREATE TABLE `calendar` (
  `service_id` varchar(255) DEFAULT NULL,
  `monday` int(11) DEFAULT NULL,
  `tuesday` int(11) DEFAULT NULL,
  `wednesday` int(11) DEFAULT NULL,
  `thursday` int(11) DEFAULT NULL,
  `friday` int(11) DEFAULT NULL,
  `saturday` int(11) DEFAULT NULL,
  `sunday` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  KEY `service_id` (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table calendar_dates
# ------------------------------------------------------------

DROP TABLE IF EXISTS `calendar_dates`;

CREATE TABLE `calendar_dates` (
  `service_id` varchar(255) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `exception_type` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table routes
# ------------------------------------------------------------

DROP TABLE IF EXISTS `routes`;

CREATE TABLE `routes` (
  `route_id` varchar(255) DEFAULT NULL,
  `agency_id` varchar(255) DEFAULT NULL,
  `route_short_name` varchar(255) DEFAULT NULL,
  `route_long_name` varchar(255) DEFAULT NULL,
  `route_desc` longtext,
  `route_type` int(11) DEFAULT NULL,
  `route_url` varchar(255) DEFAULT NULL,
  `route_color` varchar(255) DEFAULT NULL,
  `route_text_color` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table shapes
# ------------------------------------------------------------

DROP TABLE IF EXISTS `shapes`;

CREATE TABLE `shapes` (
  `shape_id` varchar(255) DEFAULT NULL,
  `shape_pt_lat` float(10,6) DEFAULT NULL,
  `shape_pt_lon` float(10,6) DEFAULT NULL,
  `shape_pt_sequence` int(11) DEFAULT NULL,
  `shape_dist_traveled` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table stop_times
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stop_times`;

CREATE TABLE `stop_times` (
  `trip_id` varchar(255) DEFAULT NULL,
  `arrival_time` time DEFAULT NULL,
  `departure_time` time DEFAULT NULL,
  `stop_id` varchar(255) DEFAULT NULL,
  `stop_sequence` int(11) DEFAULT NULL,
  `stop_headsign` varchar(255) DEFAULT NULL,
  `pickup_type` int(11) DEFAULT NULL,
  `drop_off_type` int(11) DEFAULT NULL,
  `shape_dist_traveled` varchar(255) DEFAULT NULL,
  KEY `stop_time_index` (`trip_id`,`arrival_time`,`departure_time`,`stop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table stops
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stops`;

CREATE TABLE `stops` (
  `stop_id` varchar(255) DEFAULT NULL,
  `stop_code` varchar(255) DEFAULT NULL,
  `stop_name` varchar(255) DEFAULT NULL,
  `stop_desc` varchar(255) DEFAULT NULL,
  `stop_lat` float(10,6) DEFAULT NULL,
  `stop_lon` float(10,6) DEFAULT NULL,
  `zone_id` varchar(255) DEFAULT NULL,
  `stop_url` varchar(255) DEFAULT NULL,
  `location_type` int(11) DEFAULT NULL,
  `parent_station` varchar(255) DEFAULT NULL,
  KEY `stop_id` (`stop_id`,`stop_name`,`stop_lat`,`stop_lon`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table transfers
# ------------------------------------------------------------

DROP TABLE IF EXISTS `transfers`;

CREATE TABLE `transfers` (
  `from_stop_id` varchar(255) DEFAULT NULL,
  `to_stop_id` varchar(255) DEFAULT NULL,
  `transfer_type` int(11) DEFAULT NULL,
  `min_transfer_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table trips
# ------------------------------------------------------------

DROP TABLE IF EXISTS `trips`;

CREATE TABLE `trips` (
  `route_id` varchar(11) DEFAULT NULL,
  `service_id` varchar(255) DEFAULT NULL,
  `trip_id` varchar(255) DEFAULT NULL,
  `trip_headsign` varchar(255) DEFAULT NULL,
  `direction_id` int(11) DEFAULT NULL,
  `block_id` varchar(255) DEFAULT NULL,
  `shape_id` varchar(255) DEFAULT NULL,
  KEY `route_id` (`route_id`,`service_id`,`trip_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
