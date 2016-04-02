<?php

error_reporting(E_ALL);
set_time_limit(0);

/* Включает скрытое очищение вывода так что мы получаем данные
 * как только они появляются. */
ob_implicit_flush();

include 'HttpServer.php';

$server = new HttpServer();
