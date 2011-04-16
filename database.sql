-- phpMyAdmin SQL Dump
-- version 3.3.7deb5build0.10.10.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 16, 2011 at 05:43 PM
-- Server version: 5.1.49
-- PHP Version: 5.3.3-1ubuntu9.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `regnskap`
--

-- --------------------------------------------------------

--
-- Table structure for table `bankaccounts`
--

CREATE TABLE IF NOT EXISTS `bankaccounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `num` varchar(255) NOT NULL,
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

