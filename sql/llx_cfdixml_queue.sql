 CREATE TABLE IF NOT EXISTS `llx_cfdixml_queue` (
    `rowid` int(11) NOT NULL AUTO_INCREMENT, 
    `fk_object` int(11) NOT NULL,
    `type` varchar(255) NOT NULL, /* facture, payment */
    `reason` varchar(2) NULL, /* reason cancel */
    `active` int(11) NOT NULL DEFAULT '1', /* 0 no run, 1 run */
     `note` varchar(255) NOT NULL, /* message description */
    PRIMARY KEY (`rowid`) 
    );