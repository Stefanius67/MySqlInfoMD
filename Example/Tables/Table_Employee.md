# Table: Employee

[Overview](../index.md)

**Employee adresses**

|Field|Type|Null|Key|Default|Comment|
|-----|----|-|:-:|-------|-------|
|`lID`|int|![No](../images/unchecked.png "Not NULL")|![PRI](../images/pri_key.png "Primary Key")|*not set*|Primary Key|
|`lSectionID`|int|![Yes](../images/checked.png "Allows NULL")|![MUL](../images/mul_key.png "Index")|*null*|FK to Section table|
|`lEmployeeNr`|int|![No](../images/unchecked.png "Not NULL")|![UNI](../images/uni_key.png "Unique Key")|*not set*|Unique employee number|
|`strFirstName`|varchar(50)|![No](../images/unchecked.png "Not NULL")||*empty*||
|`strLastName`|varchar(50)|![No](../images/unchecked.png "Not NULL")||*empty*||
|`strStreet`|varchar(50)|![No](../images/unchecked.png "Not NULL")||*empty*||
|`strPostcode`|varchar(10)|![No](../images/unchecked.png "Not NULL")||*empty*||
|`strCity`|varchar(50)|![No](../images/unchecked.png "Not NULL")||*empty*||
|`strCountry`|varchar(50)|![No](../images/unchecked.png "Not NULL")||*empty*|country code (ISO 3166 ALPHA-2)|
|`dateBirth`|date|![Yes](../images/checked.png "Allows NULL")||*null*|birthday|
|`dateEntry`|date|![Yes](../images/checked.png "Allows NULL")||*null*|date of entry|


## References to other Tables
|Column|Reference to|UPDATE|DELETE|
|------|------------|------|------|
|`lSectionID`|`Section` . `lID`|RESTRICT|RESTRICT|


## Tables referencing this Table
|Column|Referenced by|UPDATE|DELETE|
|------|-------------|------|------|
|`lID`|`Board` . `lEmployeeID`|CASCADE|SET NULL|
## Table Create Statement: 

```SQL
CREATE TABLE `Employee` (
  `lID` int NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `lSectionID` int DEFAULT NULL COMMENT 'FK to Section table',
  `lEmployeeNr` int NOT NULL COMMENT 'Unique employee number',
  `strFirstName` varchar(50) COLLATE utf8mb3_german2_ci NOT NULL DEFAULT '',
  `strLastName` varchar(50) COLLATE utf8mb3_german2_ci NOT NULL DEFAULT '',
  `strStreet` varchar(50) COLLATE utf8mb3_german2_ci NOT NULL DEFAULT '',
  `strPostcode` varchar(10) COLLATE utf8mb3_german2_ci NOT NULL DEFAULT '',
  `strCity` varchar(50) COLLATE utf8mb3_german2_ci NOT NULL DEFAULT '',
  `strCountry` varchar(50) COLLATE utf8mb3_german2_ci NOT NULL DEFAULT '' COMMENT 'country code (ISO 3166 ALPHA-2)',
  `dateBirth` date DEFAULT NULL COMMENT 'birthday',
  `dateEntry` date DEFAULT NULL COMMENT 'date of entry',
  PRIMARY KEY (`lID`),
  UNIQUE KEY `lEmployeeNr` (`lEmployeeNr`),
  KEY `lSectionID` (`lSectionID`),
  CONSTRAINT `Employee_ibfk_1` FOREIGN KEY (`lSectionID`) REFERENCES `Section` (`lID`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_german2_ci COMMENT='Employee adresses'
```
