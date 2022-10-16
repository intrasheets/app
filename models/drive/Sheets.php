<?php

namespace models\drive;

use models\drive\Drive;

class Sheets extends Drive
{
    // Table configuration
    public $config = array(
        'tableName' => 'drive.sheets',
        'primaryKey' => 'sheet_id',
        'sequence' => 'drive.sheet_sheet_id_seq',
        'recordId' => 0
    );

    public function getId($sheet_guid)
    {
        $result = $this->database->table('drive.sheets')
            ->column('sheet_id')
            ->bindParam('sheet_guid = :sheet_guid', $sheet_guid)
            ->bindParam('sheet_status = :sheet_status', 1)
            ->execute();

        if ($row = $this->database->fetch_assoc($result)) {
            return $row['sheet_id'];
        }

        return false;
    }

    public function getByGuid($sheet_guid)
    {
        $result = $this->database->table('drive.sheets')
            ->column('sheet_id, sheet_guid, sheet_privacy, sheet_description, user_id, sheet_config')
            ->bindParam('sheet_guid = :sheet_guid', $sheet_guid)
            ->bindParam('sheet_status = :sheet_status', 1)
            ->execute();

        return $this->database->fetch_assoc($result);
    }

    public function getAll($userId)
    {
        $data = [];

        $result = $this->database->table('drive.sheets')
            ->column('sheet_id as id, sheet_guid as guid, sheet_privacy as privacy, sheet_description as description, sheet_updated as updated')
            ->argument(1, 'sheet_status', 1)
            ->argument(2, 'user_id', $userId)
            ->execute();

        while ($row = $this->database->fetch_assoc($result)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     * The request is allowed
     * @param int $user_id
     * @param string $guid
     * @return boolean
     */
    public function isAllowed($user_id, $guid)
    {
        if ($row = $this->getByGuid($guid)) {
            if (! $row['sheet_privacy'] || ($row['sheet_privacy'] && $row['user_id'] === $user_id)) {
                return 4; // Owner
            } else {
                // Invited users
                $users = new \models\SheetsUsers;
                return $users->isAllowed($user_id, $row['sheet_id']);
            }
        }

        return false;
    }
}
