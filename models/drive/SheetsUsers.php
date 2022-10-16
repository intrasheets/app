<?php

namespace models\drive;

use bossanova\Model\Model;

class SheetsUsers extends Model
{
    public $config = array(
        'tableName' => 'sheets_users',
        'primaryKey' => 'sheet_user_id',
        'sequence' => 'sheets_users_sheet_user_id_seq',
        'recordId' => 0
    );

    public function setTokenOnwer($sheet_user_id, $user_id)
    {
        $this->database->table('drive.sheets_users')
            ->column([ 'user_id' => $user_id, 'sheet_user_status' => 1 ])
            ->bindParam('sheet_user_id = :sheet_user_id', $sheet_user_id)
            ->update()
            ->execute();
    }

    /**
     * @param int $userId
     * @param string $sheet_guid
     * @return bool
     */
    public function getByToken($sheet_id, $token)
    {
        $result = $this->database->table('drive.sheets_users')
            ->column('sheet_user_id, sheet_user_level')
            ->bindParam('sheet_id = :sheet_id', $sheet_id)
            ->bindParam('sheet_user_status > :sheet_user_status', 0)
            ->bindParam('sheet_user_token = :sheet_user_token', $token)
            ->execute();

        if ($row = $this->database->fetch_assoc($result)) {
            return $row;
        }

        return false;
    }

    /**
     * @param int $userId
     * @param string $sheet_guid
     * @return bool
     */
    public function isOwner($userId, $sheet_id)
    {
        $result = $this->database->table('drive.sheets')
            ->column('count(sheet_id)')
            ->bindParam('sheet_id = :sheet_id', $sheet_id)
            ->bindParam('sheet_status = :sheet_status', 1)
            ->bindParam('user_id = :user_id', $userId)
            ->execute();

        if ($this->database->fetch_assoc($result)) {
            return true;
        }

        return false;
    }

    /**
     * Get the level from one user over a sheets
     * @param number $userId
     * @param number $sheet_id
     * @return boolean
     */
    public function getLevel($userId, $sheet_id)
    {
        $result = $this->database->table('drive.sheets_users')
            ->column('sheet_id, sheet_user_level')
            ->bindParam('sheet_id = :sheet_id', $sheet_id)
            ->bindParam('sheet_user_status = :sheet_status', 1)
            ->bindParam('user_id = :user_id', $userId)
            ->execute();

        if ($row = $this->database->fetch_assoc($result)) {
            return $row['sheet_user_level'];
        }

        return false;
    }

    public function isAllowed($userId, $sheet_id)
    {
        $result = $this->database->table('drive.sheets_users')
            ->column('sheet_id, sheet_user_level')
            ->bindParam('sheet_id = :sheet_id', $sheet_id)
            ->bindParam('sheet_user_status = :sheet_status', 1)
            ->bindParam('user_id = :user_id', $userId)
            ->execute();

        if ($row = $this->database->fetch_assoc($result)) {
            return $row['sheet_id'] === $sheet_id ? $row['sheet_user_level'] : false;
        }

        return false;
    }

    /**
     * @param string $sheet_guid sheet_guid
     * @return array
     */
    public function getUsers($sheet_guid)
    {
        $sheet_guid = $this->database->bind($sheet_guid);

        $result = $this->database->table('drive.sheets_users u')
            ->column('u.user_id, u.sheet_user_email, u.sheet_user_level, u.sheet_user_token')
            ->argument(1, 'sheet_guid', $sheet_guid)
            ->argument(2, 'sheet_user_status', 0, '>')
            ->execute();

        $data = [];

        while ($row = $this->database->fetch_assoc($result)) {
            $data[] = $row;
        }

        return $data;
    }

}
