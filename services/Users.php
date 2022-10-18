<?php

namespace services;

use bossanova\Mail\Mail;
use bossanova\Config\Config;
use bossanova\Services\Services;

class Users extends Services
{
    public $model = null;
    public $permissions = null;

    public function exists($k, $v)
    {
        return $this->model->exists($k, $v);
    }

    function processData($data) {
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

    public function api($user_id)
    {
        $jwt = new \bossanova\Jwt\Jwt();

        // Get the signed token
        $bearer = $jwt->setToken([
            'exp' => date("Y-m-d H:i:s", strtotime("+1 minutes")),
            'user_id' => $user_id,
            'scope' => ['signature'],
        ]);

        $result = $this->request($bearer);
        if ($result !== false) {
            $jwt = new \bossanova\Jwt\Jwt;
            $jwt->user_signature = $result;
            $jwt->save();

            $user = $this->model->get($user_id);
            $user->user_signature = $result;
            $user->save();

            return [ 'key' => $jwt->getToken(true) ];
        } else {
            return [
                'error' => 1,
                'message' => 'Something went wrong.'
            ];
        }
    }

    public function request($bearer) {
        $headers = [
            'Accept: text/json',
            'Authorization: Bearer eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJkb21haW4iOiJqc3ByZWFkc2hlZXQuY29tIiwidXNlcl9pZCI6MSwidXNlcl9sb2dpbiI6InBhdWxob2RlbCIsInVzZXJfbmFtZSI6IkpzcHJlYWRzaGVldCIsInVzZXJfc2lnbmF0dXJlIjoiYmIwZTc5ZmFmMzUwYmNlM2JhY2E5Y2RiMzdkMjhiNzdkNGFkMzliNCIsInBhcmVudF9pZCI6MCwicGVybWlzc2lvbl9pZCI6MSwibG9jYWxlIjoicHRfQlIiLCJleHBpcmF0aW9uIjoxNjY2MTAxNDgzLCJwZXJtaXNzaW9ucyI6W10sImNvdW50cnlfaWQiOjAsImhhc2giOiJGVDJpMmtDQUsxamFwQm41WUF6dkhmTlRHSWZoX2RPR1VMT2J2cTBYM2dOQk9rczZ3Ym5BcG5TNlFOUHE5VDVEMEN4T1J0RFRZc0x1VWsxMFROYnBFZyJ9.5VRmG_BnRzhtsLxgyYWeWct5BVGoyu1LLF6Xd0e4VTf0UeNcpcjmSrI5lkXf-iZfVdlOhaq7LZxI5L0omXG-vQ',
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];

        // URL
        $curl = curl_init($_ENV['INTRASHEETS_SERVER']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_VERBOSE, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($curl);

        if ($response) {
            $data = json_decode($response);
            if (isset($data['signature']) && $data['signature']) {
                return $data['signature'];
            }
        }

        return false;
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
                            $row['url'] = APPLICATION_URL . '/login';
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
     * Select Permissions
     *
     * @param integer $user_id
     * @return array   $data
     */
    public function getPermissions($id = null)
    {
        $permissions = $this->permissions->combo();

        return count($permissions) > 0 ? $permissions : ['error' => 1, 'message' => '^^[No record found]^^'];
    }

    /**
     * Check existing user
     *
     */
    public function getById($id)
    {
        $data = $this->model->getById($id);

        return $data;
    }

    public static function guidv4()
    {
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}