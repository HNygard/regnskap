CREATE TABLE IF NOT EXISTS `registreringskontoer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `navn` varchar(25) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `bankkontoer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nr` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `banktransaksjoner` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `betdato` int(11) NOT NULL,
  `rentedato` int(11) NOT NULL,
  `beskrivelse` varchar(255) NOT NULL,
  `belop` double NOT NULL,
  `bankkonto_nr` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
