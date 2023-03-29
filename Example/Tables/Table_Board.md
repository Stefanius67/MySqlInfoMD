# Table: Board

[Overview](../index.md)

**Board of the company**

|Field|Type|Null|Key|Default|Comment|
|-----|----|-|:-:|-------|-------|
|`lID`|int|![No](../images/unchecked.png "Not NULL")|![PRI](../images/pri_key.png "Primary Key")|*not set*|Primary Key|
|`strPosition`|varchar(50)|![No](../images/unchecked.png "Not NULL")||*not set*||
|`lEmployeeID`|int|![Yes](../images/checked.png "Allows NULL")|![MUL](../images/mul_key.png "Index")|*null*|FK to Employee table|


## References to other Tables
|Column|Reference to|UPDATE|DELETE|
|------|------------|------|------|
|`lEmployeeID`|`Employee` . `lID`|CASCADE|SET NULL|
## Table Create Statement: 

```SQL
CREATE TABLE `Board` (
  `lID` int NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `strPosition` varchar(50) COLLATE utf8mb3_german2_ci NOT NULL,
  `lEmployeeID` int DEFAULT NULL COMMENT 'FK to Employee table',
  PRIMARY KEY (`lID`),
  KEY `lEmployeeID` (`lEmployeeID`),
  CONSTRAINT `Board_ibfk_1` FOREIGN KEY (`lEmployeeID`) REFERENCES `Employee` (`lID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_german2_ci COMMENT='Board of the company'
```
