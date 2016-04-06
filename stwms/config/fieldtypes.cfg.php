<?php
return array(
 'varchar'=> array(
           'name'=>'字符型',
           'access'=>'text',
           'mssql'=>'nvarchar', //可变长度的Unicode 数据，最长为 4,000 个字符。 
           'mysql'=>'varchar',
           'sqlite'=>'varchar'
          ),
 'text'=> array(
           'name'=>'文本型',
           'access'=>'memo',
           'mssql'=>'ntext', //这种数据类型能存储 230 -1或将近10亿个字符，且使用的字节空间增加了一倍。
           'mysql'=>'mediumtext',
           'sqlite'=>'mediumtext' 
          ),
 'tinyint'=> array(
           'name'=>'字节型',
           'access'=>'byte',
           'mssql'=>'tinyint', //数据类型能存储从0到255 之间的整数。
           'mysql'=>'tinyint',
           'sqlite'=>'tinyint'
          ),
 'smallint'=> array(
           'name'=>'小整型',
           'access'=>'short',
           'mssql'=>'smallint',// 2 字节（16 位）数据类型，存储位于 -2^15 (-32,768) 与 2^15 - 1 (32,767) 之间的数字。
           'mysql'=>'smallint',
           'sqlite'=>'smallint'
          ),
 'int'=> array(
           'name'=>'整型',
           'access'=>'long',
           'mssql'=>'int', //数据类型可以存储从- 231(-2147483648)到231 (2147483 647)之间的整数
           'mysql'=>'int',
           'sqlite'=>'int'
          ),
 'float'=> array(
           'name'=>'浮点型',
           'access'=>'double',
           'mssql'=>'float', //浮点数可以是从-1.79E+308到1.79E+308 之间的任意数
           'mysql'=>'float',
           'sqlite'=>'float'
          )
);
?>
