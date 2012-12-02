CREATE TABLE `url_shorten` (
  `idUrlShorten` char(255) NOT NULL,
  `shortenerHash` char(255) NOT NULL,
  `originalUrl` text NOT NULL,
  PRIMARY KEY (`idUrlShorten`),
  INDEX `shortenerHash`(`shortenerHash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci