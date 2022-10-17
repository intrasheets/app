<?php

namespace services\drive;

use bossanova\Services\Services;

class Drive extends Services
{
    public function getAccess($id)
    {
        if ($row = $this->model->getByGuid($id)) {
            if ($row['user_id'] === $this->user_id) {
                // owner
                return 4;
            } else if (! $row['sheet_privacy']) {
                // public level as editor
                return 3;
            } else {
                $users = new \models\drive\SheetsUsers;

                if ($this->token) {
                    $ret = $users->getByToken($row['sheet_id'], $this->token);
                    if ($ret) {
                        if (isset($ret['user_id']) && $ret['user_id']) {
                            if ($ret['user_id'] === $this->user_id) {
                                return $ret['sheet_user_level'];
                            }
                        } else {
                            if ($this->user_id) {
                                $model = new \models\drive\SheetsUsers;
                                $model->setTokenOnwer($ret['sheet_user_id'], $this->user_id);
                            }
                            return $ret['sheet_user_level'];
                        }
                    }
                } else if ($this->user_id) {
                    return $users->isAllowed($this->user_id, $row['sheet_id']);
                }
            }
        }

        return false;
    }

    public function delete($guid)
    {
        $result = $this->model->deleteByUserAndGuid($this->user_id, $guid);

        if ($result) {
            return [
                'success' => 1,
                'message' => 'Successfully saved',
            ];
        } else {
            return [
                'error' => 1,
                'message' => 'Something went wrong',
            ];
        }
    }

    public function update($id, $data)
    {
        if (! $this->isOwner($id)) {
            $data = [
                'error' => 1,
                'message' => '^^[Permission denied]^^'
            ];
        } else {
            $data = $this->model->updateByGuidAndUser($this->user_id, $id, $data);

            if (! $data) {
                $data = [
                    'error' => 1,
                    'message' => '^^[It was not possible to delete your record]^^: '
                        . $this->model->getError()
                ];
            } else {
                $data = [
                    'success' => 1,
                    'message' => '^^[Successfully deleted]^^',
                ];
            }
        }

        return $data;
    }

    public function getChanged()
    {
        return $this->model->getChanged();
    }

    public function getByGuid($guid)
    {
        return $this->model->getByGuid($guid);
    }

    public function getAll($user_id)
    {
        return $this->model->getAll($user_id);
    }

    public function isOwner($id)
    {
        return $this->model->isOwner($this->user_id, $id);
    }

    public function isAllowed($id, $data = null)
    {
        return $this->model->isAllowed($this->user_id, $id);
    }
}