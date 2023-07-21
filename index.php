<?php
require './shortener.php';
require './config.php';

$shortener = new Shortener($hostname, $connection);

$shortener->set_chars($chars);
$shortener->set_salt($salt);
$shortener->set_padding($padding);

$shortener->run();
?>
