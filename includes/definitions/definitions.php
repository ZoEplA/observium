#!/usr/bin/env php
<?php

    $config_tmp = file_get_contents('/home/administrator/definitions.dat');
    $config_tmp = gzuncompress($config_tmp);
    $config_tmp = unserialize($config_tmp);

    var_dump($config_tmp);
?>
