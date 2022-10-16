<?php

namespace models\drive;

use bossanova\Model\Model;

class Drive extends Model
{
    /**
     * Create a new online table
     */
    public function create($column)
    {
        $info = $this->database->bind($column);

        $this->database->table($this->config->tableName)
            ->column($info)
            ->insert()
            ->execute();

        return $this->hasSuccess();
    }

}