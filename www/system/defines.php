<?php

/*
 * Домашняя ссылка
 */
$HomeUri = '';
if(isset($_SERVER['HTTP_HOST'])){
    $protokol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
    $HomeUri = $protokol.$_SERVER['HTTP_HOST'];
}
define('_HOME_URL_', $HomeUri);



