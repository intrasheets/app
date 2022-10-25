<?php

namespace modules\Media;

class Media
{
    public function __construct()
    {
        $d = explode('/', $_SERVER['REQUEST_URI']);
        $d[1] = 'api';
        $d[4] = $d[3];
        $d[3] = 'image';
        $d = implode('/', $d);

        header("HTTP/1.1 301 Moved Permanently");
        header("Location: {$d}");

        exit();
    }
}
