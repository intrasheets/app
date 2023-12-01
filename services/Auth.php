<?php

namespace services;

use bossanova\Jwt\Jwt;
use bossanova\Common\Wget;
use bossanova\Render\Render;

class Auth extends \bossanova\Auth\Auth
{
    use Wget;

    public function __construct()
    {
        $this->user = new \models\Users;
    }

    public function login()
    {
        if ($option = $this->getPost('social')) {
            // Process social login
            if ($option == 'google') {
                return $this->googleTokenLogin($this->getPost('token'));
            }
        } else {
            // Login
            return parent::login();
        }
    }

    /**
     * Perform authentication
     *
     * @param array $row
     * @param string $message
     */
    public function authenticate($row)
    {
        // Load permission services
        $permissions = new \services\Permissions(new \models\Permissions());

        // Jwt
        $jwt = new Jwt;

        // Signature
        $signature = hash('sha512', uniqid(mt_rand(), true));

        // Expires
        $expires = 0;

        // Remove cookie in 30 days
        if ($this->getPost('remember')) {
            $expires = time() + 86400 * 30;
        }

        // Cookie data
        $data = [
            // Domain
            'iss' => Render::getDomain(),
            // Expires in one week
            'exp' => time() + 86400 * 7,
            // When it was created
            'iat' => time(),
            // Sub
            'sub' => $row['user_id'],
            // Routes
            'scope' => $permissions->getPermissionsById($row['permission_id']),
            // Other properties
            'user_id' => $row['user_id'],
            'user_login' => $row['user_login'],
            'user_name' => $row['user_name'],
            'user_signature' => isset($row['user_signature']) && $row['user_signature'] ? $row['user_signature'] : '',
            'parent_id' => $row['parent_id'],
            'permission_id' => $row['permission_id'],
            'locale' => $row['user_locale'],
            'country_id' => isset($row['country_id']) && $row['country_id'] ? $row['country_id'] : 0,
            'hash' => $jwt->sign($signature),
        ];

        // User image
        if (isset($row['user_image']) && $row['user_image']) {
            $data['user_image'] = $row['user_image'];
        }

        // Payload
        $token = $jwt->set($data)->save($expires);

        // Backend signature control
        if (class_exists('Redis')) {
            if ($redis = \bossanova\Redis\Redis::getInstance()) {
                // Save signature
                $redis->set('hash' . $row['user_id'], $signature);
            }
        }

        return $token;
    }

    /**
     * Facebook integration
     * @param string $token
     * @return string $json
     */
    public function googleTokenLogin($token)
    {
        $data = [];

        if (defined('BOSSANOVA_LOGIN_VIA_GOOGLE') && BOSSANOVA_LOGIN_VIA_GOOGLE == true) {

            // Token URL verification
            $url = "https://oauth2.googleapis.com/tokeninfo?id_token=$token";
            // Validate token
            $result = $this->wget($url);

            // Valid token
            if (isset($result['aud']) && $result['aud'] && $result['aud'] === GOOGLE_API_CLIENT_ID) {
                // User id found
                if (isset($result['sub']) && $result['sub']) {
                    // Locate user
                    $row = $this->user->getUserByGoogleId($result['sub']);

                    // User not found by google id
                    if (! isset($row['user_id'])) {
                        // Check if this user exists in the database by email
                        if (isset($result['email']) && $result['email']) {
                            // Try to find the user by email
                            $row = $this->user->getUserByEmail($result['email']);

                            if (isset($row['user_id']) && $row['user_id']) {
                                if (isset($row['google_id']) && $row['google_id']) {
                                    // An user with
                                    return [
                                        'error' => 1,
                                        'message' => '^^[This account already exists bound to another account.]^^',
                                    ];
                                } else {
                                    if ($password = $this->getPost('password')) {
                                        // Posted password
                                        $password = hash('sha512', $password . $row['user_salt']);
                                        // Check to see if password matches
                                        if ($password == $row['user_password'] && strtolower($row['user_email']) == strtolower($result['email']) && $row['user_status'] == 1) {
                                            $this->user->google_id = $result['sub'];
                                        } else {
                                            // There are one account with this email. Ask the user what he wants to do.
                                            return [
                                                'error' => 1,
                                                'message' => '^^[Invalid password]^^',
                                            ];
                                        }
                                    } else {
                                        // There are one account with this email. Ask the user what he wants to do.
                                        return [
                                            'success' => 1,
                                            'message' => '^^[There are an account with your email. Would you like to bound both accounts? Please enter your account password.]^^',
                                            'action' => 'bindSocialAccount',
                                        ];
                                    }
                                }
                            }
                        }
                    }

                    $isNewUser = false;

                    // Create a new user
                    if (defined('BOSSANOVA_NEWUSER_VIA_GOOGLE') && BOSSANOVA_NEWUSER_VIA_GOOGLE == true) {
                        if (! isset($row['user_id'])) {
                            if (! $this->getPost('terms')) {
                                return [
                                    'success' => 1,
                                    'message' => 'Please review and accept our terms and conditions to proceed.',
                                    'action' => 'acceptTermsAndConditions'
                                ];
                            } else {
                                $row = [
                                    'google_id' => $result['sub'],
                                    'user_name' => $result['given_name'],
                                    'user_login' => '',
                                    'user_email' => isset($result['email']) ? $result['email'] : '',
                                    'user_terms' => $this->getPost('terms'),
                                    'user_status' => 1,
                                ];

                                $u = new \services\Users();
                                $row = $u->addNewUser($row);

                                if (isset($row['user_id']) && $row['user_id']) {
                                    $isNewUser = true;
                                    // Load user data as object
                                    $this->user->get($row['user_id']);
                                } else {
                                    return [
                                        'error' => 1,
                                        'message' => 'Something went wrong',
                                    ];
                                }
                            }
                        }
                    }

                    if (isset($row['user_id']) && $row['user_id']) {
                        // Message
                        $this->message = '^^[User authenticated from google token]^^';

                        // Authenticated
                        $this->authenticate($row);

                        $data = [
                            'success' => 1,
                            'message' => $this->message,
                            'url' => Render::getLink(Render::$urlParam[0]),
                        ];

                        if ($isNewUser === true) {
                            $data['id'] = $row['user_id'];
                            $data['name'] = $row['user_name'];
                            $data['email'] = $row['user_email'];
                        } else {
                            // Force login by hash for specific use
                            $this->user->user_hash = '';
                            $this->user->user_recovery = '';
                            $this->user->user_recovery_date = '';

                            // Update user information
                            $this->user->save();
                        }
                    } else {
                        $data = [
                            'error' => 1,
                            'message' => "^^[User not authenticated]^^",
                            'url' => Render::getLink(Render::$urlParam[0] . '/login'),
                        ];
                    }
                }
            } else {
                $data = [
                    'error' => 1,
                    'message' => "^^[Invalid google token]^^",
                    'url' => Render::getLink(Render::$urlParam[0] . '/login'),
                ];
            }
        } else {
            $data = [
                'error' => 1,
                'message' => "^^[Action not allowed]^^",
                'url' => Render::getLink(Render::$urlParam[0] . '/login'),
            ];
        }

        return $data;
    }
}