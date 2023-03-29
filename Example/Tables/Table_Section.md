# Table: Section

[Overview](../index.md)

**Company sections**

|Field|Type|Null|Key|Default|Comment|
|-----|----|-|:-:|-------|-------|
|`lID`|int|![No](../images/unchecked.png "Not NULL")|![PRI](../images/pri_key.png "Primary Key")|*not set*|Primary Key|
|`strName`|varchar(50)|![No](../images/unchecked.png "Not NULL")||*empty*||
|`strDescription`|text|![No](../images/unchecked.png "Not NULL")||*not set*||


## Tables referencing this Table
|Column|Referenced by|UPDATE|DELETE|
|------|-------------|------|------|
|`lID`|`Employee` . `lSectionID`|RESTRICT|RESTRICT|
## Table Create Statement: 

```SQL
CREATE TABLE `Section` (
  `lID` int NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `strName` varchar(50) COLLATE utf8mb3_german2_ci NOT NULL DEFAULT '',
  `strDescription` text COLLATE utf8mb3_german2_ci NOT NULL,
  PRIMARY KEY (`lID`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_german2_ci COMMENT='Company sections'
```
