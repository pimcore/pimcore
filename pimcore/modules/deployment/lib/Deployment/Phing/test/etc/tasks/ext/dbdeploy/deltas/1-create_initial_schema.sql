--//

CREATE TABLE `post` (
    `title` VARCHAR(255),
    `time_created` DATETIME,
    `content` MEDIUMTEXT
);

--//@UNDO

DROP TABLE `post`;

--//
