<?php

namespace models\drive;

use bossanova\Model\Model;

class Signature extends Model
{
    public $config = array(
        'tableName' => 'drive.signature',
        'primaryKey' => 'signature_id',
        'sequence' => 'drive.signature_signature_id_seq',
        'recordId' => 0
    );

    /**
     * Create a new online table
     */
    public function save()
    {
        $this->database->table($this->config->tableName)
            ->argument(1, "user_id", $this->user_id)
            ->delete()
            ->execute();

        $column = [
            'user_id' => $this->user_id,
            'user_signature' => $this->user_signature
        ];

        $this->database->table($this->config->tableName)
            ->column($column, true)
            ->insert()
            ->execute();

        return $this->hasSuccess();
    }
}