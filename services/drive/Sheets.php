<?php

namespace services\drive;

class Sheets extends Drive
{
    public function setTokenOwner($sheet_user_id)
    {
        $users = new \models\drive\SheetsUsers;
        return $users->setTokenOnwer($sheet_user_id, $this->user_id);
    }

    public function getToken($token)
    {
        return $this->model->getToken($token);
    }
}