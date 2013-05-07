-- Create syntax for TABLE 'api_xero'
CREATE TABLE `api_xero` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `access_token` text,
  `access_token_secret` text,
  `oauth_session_handle` text,
  `connection_start` int(50) DEFAULT NULL,
  `default_sales_account` int(11) NOT NULL,
  `default_purchases_account` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` enum('Connected','Disconnected') NOT NULL DEFAULT 'Disconnected',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- Create syntax for TABLE 'api_xero_responses'
CREATE TABLE `api_xero_responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ErrorNumber` int(11) NOT NULL,
  `Type` varchar(125) NOT NULL,
  `Message` text NOT NULL,
  `Message_2` text NOT NULL,
  `Occured` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='store all error responses from Xero API';