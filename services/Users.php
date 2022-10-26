<?php

namespace services;

use bossanova\Mail\Mail;
use bossanova\Config\Config;
use bossanova\Services\Services;

class Users extends Services
{
    public $model = null;
    public $permissions = null;

    function processPost($data)
    {
        if (isset($data['user_signature'])) {
            unset($data['user_signature']);
        }

        return $data;
    }

    /**
     * Process the profile data before sending to the frontend
     * @param $data
     * @return mixed
     */
    function processData($data)
    {
        // Create a self contained JWT as Bearer
        if (isset($data['user_signature']) && $data['user_signature']) {
            $signature = [
                'user_id' => $data['user_id'],
                'user_signature' => $data['user_signature'],
            ];

            $jwt = new \bossanova\Jwt\Jwt;
            $signature = $jwt->setToken($signature);
        } else {
            $signature = '';
        }

        $data['user_signature'] = $signature;
        $data['user_password'] = '';

        return $data;
    }

    /**
     * Select
     *
     * @param integer $user_id
     * @return array   $data
     */
    public function select($id)
    {
        $data = [];

        if ((int)$id > 0) {
            $data = $this->model->getById($id);

            if (count($data) > 0) {
                if (!$this->permissions->isAllowedHierarchy($data['permission_id'])) {
                    $data = [
                        'error' => 1,
                        'message' => '^^[You do not have permission to load this record]^^'
                    ];
                } else {
                    // Extra information
                    if (isset($data['user_json']) && $data['user_json']) {
                        $data['user_json'] = json_decode($data['user_json'], true);
                    }
                }
            } else {
                $data = [
                    'error' => 1,
                    'message' => '^^[No record found]^^'
                ];
            }
        }

        return $data;
    }

    /**
     * Insert
     *
     * @param array $data
     * @return array  $data
     */
    public function insert($row)
    {
        $data = [];

        // Permission is a mandatory field
        if (isset($row['permission_id'])) {
            if ($row['permission_id'] && !$this->permissions->isAllowedHierarchy($row['permission_id'])) {
                $data = [
                    'error' => 1,
                    'message' => '^^[Permission denied]^^'
                ];
            } else {
                // Avoid duplicate user email
                $user = $this->model->exists('user_email', trim($row['user_email']));

                if ((isset($user['user_id']) && $user['user_id'])) {
                    $data = [
                        'error' => 1,
                        'message' => '^^[This email is already registered to another account]^^'
                    ];
                } else {
                    $user = $this->model->exists('user_login', trim($row['user_login']));

                    if ((isset($user['user_id']) && $user['user_id'])) {
                        $data = [
                            'error' => 1,
                            'message' => '^^[This login is already registered to another account]^^'
                        ];
                    } else {
                        // Password
                        if (!isset($row['user_password']) || !$row['user_password']) {
                            $password = substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyz", 6)), 0, 6);
                        } else {
                            $password = $row['user_password'];
                        }

                        $salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
                        $pass = hash('sha512', hash('sha512', $password) . $salt);
                        $row['user_salt'] = $salt;
                        $row['user_password'] = $pass;
                        $row['user_hash'] = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
                        $row['user_status'] = 2;

                        // Avoid errors
                        if (isset($row['user_id'])) {
                            unset($row['user_id']);
                        }

                        // Extra information
                        if (isset($row['user_json'])) {
                            $row['user_json'] = json_encode($row['user_json']);
                        }

                        // Add a new record
                        $id = $this->model->column($row)->insert();

                        if (isset($id) && $id) {
                            // Get preferable mail adapter
                            $adapter = Config::get('mail');
                            // Create instance
                            $this->mail = new Mail($adapter);

                            // Loading recovery email body
                            $registrationFile = defined('EMAIL_REGISTRATION_FILE') ?
                                EMAIL_REGISTRATION_FILE : "resources/texts/registration.txt";

                            // Loading registration text template
                            $content = file_get_contents($registrationFile);

                            // Application
                            $row['url'] = $_ENV['APPLICATION_URL'] . '/login';
                            // Password plain
                            $row['user_password'] = $password;

                            // Replace macros
                            $content = $this->mail->replaceMacros($content, $row);
                            $content = $this->mail->translate($content);

                            // From configuration
                            $f = [MS_CONFIG_FROM, MS_CONFIG_NAME];

                            // Destination
                            $t = [];
                            $t[] = [$row['user_email'], $row['user_name']];

                            // Send email
                            $this->mail->sendmail($t, EMAIL_REGISTRATION_SUBJECT, $content, $f);

                            $data = [
                                'success' => 1,
                                'message' => '^^[The user has been successfully created. An email has been sent to your email address and should be confirmed]^^',
                                'id' => $id,
                            ];
                        }
                    }
                }
            }
        } else {
            $data = [
                'error' => 1,
                'message' => '^^[Permission is a mandatory field]^^'
            ];
        }

        return $data;
    }

    /**
     * Update
     *
     * @param array $data
     * @return array  $data
     */
    public function update($id, $row)
    {
        $data = $this->model->getById($id);

        if (!isset($data['permission_id'])) {
            $data['permission_id'] = 0;
        }

        if (count($data) > 0) {
            if (! $this->permissions->isAllowedHierarchy($data['permission_id'])) {
                $data = [
                    'error' => 1,
                    'message' => '^^[Permission denied]^^',
                ];
            } else {
                // Avoid duplicate user email
                $user = $this->model->exists('user_email', trim($row['user_email']));

                if ((isset($user['user_id']) && $user['user_id'] && $user['user_id'] != $id)) {
                    $data = [
                        'error' => 1,
                        'message' => '^^[This email is already in registered in another account]^^'
                    ];

                } else {
                    if (isset($row['user_login']) && $row['user_login']) {
                        $user = $this->model->exists('user_login', trim($row['user_login']));
                    }

                    if ((isset($user['user_id']) && $user['user_id'] && $user['user_id'] != $id)) {
                        $data = [
                            'error' => 1,
                            'message' => '^^[This login is already in registered in another account]^^'
                        ];
                    } else {
                        // Password
                        if (isset($row['user_password']) && !$row['user_password']) {
                            unset($row['user_password']);
                        }

                        if (isset($row['user_password'])) {
                            // Update password information
                            $salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
                            $pass = hash('sha512', hash('sha512', $row['user_password']) . $salt);
                            $row['user_salt'] = $salt;
                            $row['user_password'] = $pass;
                        }

                        // Extra information
                        if (isset($row['user_json'])) {
                            $row['user_json'] = json_encode($row['user_json']);
                        }

                        $this->model->column($row)->update($id);

                        $data = [
                            'success' => 1,
                            'message' => '^^[Successfully saved]^^'
                        ];
                    }
                }
            }
        } else {
            $data = [
                'error' => 1,
                'message' => '^^[No record found]^^'
            ];
        }

        return $data;
    }

    /**
     * Logical delete a user based on the user_id
     *
     * @param integer $user_id
     * @return array   $data
     */
    public function delete($user_id)
    {
        $data = $this->model->getById($user_id);

        if (count($data) > 0) {
            if (!$this->permissions->isAllowedHierarchy($data['permission_id'])) {
                $data = ['error' => 1, 'message' => '^^[Permission denied]^^'];
            } else {
                $this->model->delete($user_id);
                $data = ['success' => 1, 'message' => '^^[Successfully deleted]^^'];
            }
        } else {
            $data = ['error' => 1, 'message' => '^^[No record found]^^'];
        }

        return $data;
    }

    /**
     * Request a new access key and update the jwt and user profile
     * @param $user_id
     * @return array|boolean
     */
    public function generateKey($user_id)
    {
        $intrasheets = new \services\Intrasheets;
        $result = $intrasheets->requestKey($user_id);
        if ($result) {
            // Convert to array
            $data = $result;

            if (isset($data['data']) && isset($data['data']['signature']) && $data['data']['signature']) {
                // Update the users table with the new signature
                $user = $this->model->get($user_id);
                $user->user_signature = $data['data']['signature'];
                $user->save();

                // Update the current token with the new signature
                $jwt = new \bossanova\Jwt\Jwt;
                $jwt->user_signature = $data['data']['signature'];
                $jwt->save();

                // Return the new generated token
                $signature = $jwt->setToken([
                    'user_id' => $user_id,
                    'user_signature' => $data['data']['signature'],
                ]);

                return [ 'key' => $signature ];
            } else {
                return $data;
            }
        }

        return false;
    }
}