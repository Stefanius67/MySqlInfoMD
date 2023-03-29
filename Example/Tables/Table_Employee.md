# Table: Employee

[Overview](../index.md)

**Employee adresses**

|Field|Type|N|Key|Default|Comment|
|-----|----|-|:-:|-------|-------|
|`lID`|int|![](../images/unchecked.png)|![](../images/pri_key.png)|*not set*|Primary Key|
|`lSectionID`|int|![](../images/checked.png)|![](../images/mul_key.png)|*null*|FK to Section table|
|`strFirstName`|varchar(50)|![](../images/unchecked.png)||*empty*||
|`strLastName`|varchar(50)|![](../images/unchecked.png)||*empty*||
|`strStreet`|varchar(50)|![](../images/unchecked.png)||*empty*||
|`strPostcode`|varchar(10)|![](../images/unchecked.png)||*empty*||
|`strCity`|varchar(50)|![](../images/unchecked.png)||*empty*||
|`strCountry`|varchar(50)|![](../images/unchecked.png)||*empty*|country code (ISO 3166 ALPHA-2)|
|`dateBirth`|date|![](../images/checked.png)||*null*|birthday|
|`dateEntry`|date|![](../images/checked.png)||*null*|date of entry|


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
  `strFirstName` varchar(50) COLLATE utf8mb3_german2_ci NOT NULL DEFAULT '',
  `strLastName` varchar(50) COLLATE utf8mb3_german2_ci NOT NULL DEFAULT '',
  `strStreet` varchar(50) COLLATE utf8mb3_german2_ci NOT NULL DEFAULT '',
  `strPostcode` varchar(10) COLLATE utf8mb3_german2_ci NOT NULL DEFAULT '',
  `strCity` varchar(50) COLLATE utf8mb3_german2_ci NOT NULL DEFAULT '',
  `strCountry` varchar(50) COLLATE utf8mb3_german2_ci NOT NULL DEFAULT '' COMMENT 'country code (ISO 3166 ALPHA-2)',
  `dateBirth` date DEFAULT NULL COMMENT 'birthday',
  `dateEntry` date DEFAULT NULL COMMENT 'date of entry',
  PRIMARY KEY (`lID`),
  KEY `lSectionID` (`lSectionID`),
  CONSTRAINT `Employee_ibfk_1` FOREIGN KEY (`lSectionID`) REFERENCES `Section` (`lID`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_german2_ci COMMENT='Employee adresses'
```
