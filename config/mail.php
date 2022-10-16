<?php

if (MS_CONFIG_TYPE == 'sendgrid') {
    $adapter = new \bossanova\Mail\AdapterSendgrid;
} else {
    $adapter = new \bossanova\Mail\AdapterPhpmailer;
}

return $adapter;