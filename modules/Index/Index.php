<?php

namespace modules\Index;

use bossanova\Module\Module;

class Index extends Module
{
    public function __default()
    {
        header("Location:/sheets/");
    }
}
