<?php

namespace models;

use bossanova\Model\Model;

class Users extends Model
{
    // Table configuration
    public $config = array(
        'tableName' => 'users',
        'primaryKey' => 'user_id',
        'sequence' => 'users_user_id_seq',
        'recordId' => 0
    );

    /**
     * Verify value exists in the user table
     *
     * @param  string $user_email
     * @return array  $row
     */
    public function exists($k, $v)
    {
        $result = $this->database->Table($this->config->tableName)
            ->column("{$this->config->primaryKey}")
            ->argument(1, "lower($k)", "lower('$v')")
            ->execute();

        return $this->database->fetch_assoc($result);
    }

    /**
     * Logical delete a user based on the user_id
     *
     * @param  integer $user_id
     * @return array   $data
     */
    public function delete($user_id)
    {
        $this->database->table("users")
            ->column(array('user_status' => 0))
            ->argument(1, "user_id", $user_id)
            ->update()
            ->execute();

        return $this->hasSuccess();
    }

    /**
     * Get the current hash
     *
     * @param  string $user_email
     * @return array  $row
     */
    public function getUserPreviousPasswordHash($user_id, $user_password)
    {
        $user_password = $this->database->bind($user_password);

        $result = $this->database->table("users")
            ->argument(1, "user_id", $user_id)
            ->argument(2, "user_password", $user_password)
            ->execute();

        return $this->database->fetch_assoc($result) ? true : false;
    }

    /**
     * Get user by ident
     *
     * @param  string $ident - email or login
     * @return array  $row
     */
    public function getUserByIdent($ident)
    {
        $row = $this->getUserByEmail($ident);

        if (! $row) {
            $row = $this->getUserByLogin($ident);
        }

        return $row;
    }

    /**
     * Get user by email
     *
     * @param  string $user_email
     * @return array  $row
     */
    public function getUserByEmail($ident)
    {
        $ident = $this->database->bind(strtolower(trim($ident)));

        $result = $this->database->Table("users")
            ->argument(1, "lower(user_email)", "lower($ident)")
            ->execute();

        if ($row = $this->database->fetch_assoc($result)) {
            // Register user object
            $this->get($row['user_id']);
        }

        return $row;
    }

    /**
     * Get user by login
     *
     * @param  string $user_email
     * @return array  $row
     */
    public function getUserByLogin($ident)
    {
        $ident = $this->database->bind(strtolower(trim($ident)));

        $result = $this->database->Table("users")
            ->argument(1, "lower(user_login)", "lower($ident)")
            ->execute();

        if ($row = $this->database->fetch_assoc($result)) {
            // Register user object
            $this->get($row['user_id']);
        }

        return $row;
    }

    /**
     * Get user by hash
     *
     * @param  string $user_email
     * @return array  $row
     */
    public function getUserByHash($hash)
    {
        $hash = preg_replace("/[^a-zA-Z0-9]/", "", $hash);

        if ($hash && strlen($hash) == 128) {
            $hash = $this->database->bind(strtolower(trim($hash)));

            $result = $this->database->table("users")
                ->argument(1, "user_hash", $hash)
                ->execute();

            if ($row = $this->database->fetch_assoc($result)) {
                // Register user object
                $this->get($row['user_id']);
            }
        }

        return isset($row) ? $row : null;
    }

    /**
     * Get user by facebook id
     *
     * @param  string $ident
     * @return array  $row
     */
    public function getUserByFacebookId($ident)
    {
        $ident = $this->database->bind($ident);

        $result = $this->database->table("users")
            ->argument(1, 'facebook_id', $ident)
            ->execute();

        if ($row = $this->database->fetch_assoc($result)) {
            // Register user object
            $this->get($row['user_id']);
        }

        return $row;
    }

    /**
     * Get user by google id
     *
     * @param  string $ident
     * @return array  $row
     */
    public function getUserByGoogleId($ident)
    {
        $ident = $this->database->bind($ident);

        $result = $this->database->table("users")
            ->argument(1, 'google_id', $ident)
            ->execute();

        if ($row = $this->database->fetch_assoc($result)) {
            // Register user object
            $this->get($row['user_id']);
        }

        return $row;
    }

    /**
     * Update the password of a user based on a user_id
     *
     * @param integer $user_id
     * @param string $password - new password
     * @param string $hash - apply hash to the password text
     * @return void
     */
    public function setPassword($user_id, $password, $hash = false)
    {
        if (isset($password) && $password) {
            if (! $hash) {
                $password = hash('sha512', $password);
            }
            // Update user password
            $salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
            $pass = hash('sha512', $password . $salt);

            // Columns
            $column = [];
            $column['user_salt'] = $salt;
            $column['user_password'] = $pass;

            $this->database->table("users")
                ->column($column, true)
                ->argument(1, "user_id", $user_id)
                ->update()
                ->execute();
        }
    }

    public function setLog() {
    }
}
