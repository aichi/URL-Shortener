CREATE TABLE `url_shorten` (
  `idUrlShorten` char(255) NOT NULL,
  `bitlyHash` char(255) NOT NULL,
  `originalUrl` text NOT NULL,
  PRIMARY KEY (`idUrlShorten`),
  INDEX `bitly`(`bitlyHash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci