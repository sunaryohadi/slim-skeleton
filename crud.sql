# Host: localhost  (Version: 5.5.5-10.1.12-MariaDB)
# Date: 2016-03-27 18:38:55
# Generator: MySQL-Front 5.3  (Build 5.17)

/*!40101 SET NAMES utf8 */;

#
# Structure for table "crud"
#

DROP TABLE IF EXISTS `crud`;
CREATE TABLE `crud` (
  `ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `firstname` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastname` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `score` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "crud"
#

/*!40000 ALTER TABLE `crud` DISABLE KEYS */;
INSERT INTO `crud` VALUES (1,'Sunaryo','Hadi',88),(2,'Koichi','Hendrawan',92),(3,'Andy','Primawan',92);
/*!40000 ALTER TABLE `crud` ENABLE KEYS */;
