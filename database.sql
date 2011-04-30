-- phpMyAdmin SQL Dump
-- version 3.3.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 30, 2011 at 10:44 AM
-- Server version: 5.1.54
-- PHP Version: 5.3.5-1ubuntu7

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `regnskap`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE IF NOT EXISTS `accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `num` int(11) NOT NULL,
  `name` char(255) NOT NULL,
  `sum_from` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `bankaccounts`
--

CREATE TABLE IF NOT EXISTS `bankaccounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `num` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `bankaccount_autoimports`
--

CREATE TABLE IF NOT EXISTS `bankaccount_autoimports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bankaccount_id` int(11) DEFAULT NULL,
  `account_id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `text` varchar(255) NOT NULL,
  `amount_max` double DEFAULT NULL,
  `amount_min` double DEFAULT NULL,
  `time_max` int(11) DEFAULT NULL,
  `time_min` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `bankaccount_importfiles`
--

CREATE TABLE IF NOT EXISTS `bankaccount_importfiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bankaccount_id` int(11) NOT NULL,
  `filepath` varchar(255) NOT NULL,
  `from` int(11) DEFAULT NULL,
  `to` int(11) DEFAULT NULL,
  `last_imported` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `bankaccount_transactions`
--

CREATE TABLE IF NOT EXISTS `bankaccount_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bankaccount_id` int(11) NOT NULL,
  `payment_date` int(11) NOT NULL,
  `intrest_date` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `amount` double NOT NULL,
  `imported` tinyint(1) NOT NULL,
  `imported_automatically` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `registreringskontoer`
--

CREATE TABLE IF NOT EXISTS `registreringskontoer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `navn` varchar(25) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `regnskapskonto`
--

CREATE TABLE IF NOT EXISTS `regnskapskonto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `amount` double NOT NULL,
  `description` varchar(255) NOT NULL,
  `created` int(11) NOT NULL,
  `bankaccount_transaction_id` int(11) DEFAULT NULL,
  `imported_automatically` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

