kvMysqlMacros
=============

There are alot of mysql macro files out there, but mine is the simplest and neatest. You can hook up here a nice cache system as well.

So how it works. First download the **class.mysql.php** file and add it to your **includes/ folder**. Now include it to your core-file of your system, or if you are not that far in your programming experience, then include it to the file, that needs mysql queries:

    require ('class.mysql.php');

**Configuring** it works two ways. Either you can have a config file somewhere, that is gonna get included before the class.mysql.php file and the config file, will have mysql config array like this in it:

    $config_mysql = array(
        // 'server' by default is localhost
        'database' => 'database_1',
        'user' => 'user_1',
        'password' => 'password123',
        'table_prefix' => 'kv_'
    );

**Or** you simply don't use the config array and set everything up inside the class.mysql.php

**Now initializing the class:**

**If you are using the config array:**

    $DB = new DB($config_mysql);

**Else:**

    $DB = new DB;


**Usage**

Simple query:

    $DB->query("");

Query first: 

    $DB->query_first("");

Fetching:

    $names = $DB->query("SELECT * {TABLE_PREFIX}names");
    
    while ($name = $DB->fetch_array($names )) {
    
        echo $name['first] . ' ' . $name['last'];
    
    }

Also you can do query array:

    $DB->("SELECT * FROM {TABLE_PREFIX}users WHERE id IN ({ARRAY})", array(1, 4, 5, 7););

Array will be imploded with comas then.

By Kalle H. Väravas
**Copyleft - Use it freely!**
