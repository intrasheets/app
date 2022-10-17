<?php

namespace modules\Sheets;

use bossanova\Module\Module;

class Sheets extends Module
{
    public function __default()
    {
        // Get the configuration and append to the config in the template
        $area = new \stdClass;
        $area->template_area = 'config';
        $area->module_name = $this->getParam(0);
        $area->method_name = 'config';
        $this->setContent($area);
    }

    /**
     * Should return the photo of whoever is logged or default for not authenticated users
     */
    public function photo()
    {
        header("Location: /templates/default/img/no-photo.png");
    }

    /**
     * Authentication method
     */
    public function login()
    {
        if ($this->getRequestMethod() == 'POST' && isset($_POST['name'])) {
            // Register
            $service = new \services\Users(new \models\Users());
            // $data
            $data = [
                'permission_id' => 0,
                'user_name' => $this->getPost('name'),
                'user_email' => $this->getPost('username'),
                'user_login' => $this->getPost('login'),
            ];

            // Successfully created
            $data = $service->insert($data);

            // If all success create an API signature as default
            if (isset($data['id']) && $data['id']) {
                $service->api($data['id']);
            }

            return $data;

        } else {
            // Login
            if (! $this->isAjax()) {
                $this->view = [
                    'FACEBOOK_APPID' => $_ENV['FACEBOOK_APPID'],
                    'GOOGLE_API_CLIENT_ID' => $_ENV['GOOGLE_API_CLIENT_ID'],
                ];
                // Set the layout
                $this->setLayout('default/index.html');
                // Set the view
                $this->setView('login');
            }

            // Process the authentication request
            return parent::login();
        }
    }

    /**
     * Profile updates and API Key generation
     */
    public function profile()
    {
        // Get the logged userId
        if ($user_id = $this->getUser()) {
            if ($this->isAjax()) {
                // User services
                $service = new \services\Users(new \models\Users);

                if ($this->getParam(2) === 'api') {
                    // Generate a new API signature for the user
                    return $service->api($user_id);
                } else {
                    // Requirement for users profile update
                    $service->permissions = new \models\Permissions;
                    // Process the restful request
                    return $this->processRestRequest($service, $user_id);
                }
            } else {
                $this->setView('profile');
            }
        }
    }

    /**
     * Method to notify users when they are invite to share a spreadsheet.
     * This method is necessary because the API do not send emails
     */
    public function notify()
    {
        // Get the logged userId
        if ($user_id = $this->getUser()) {
            // Get the information about the guid
            $service = new \services\drive\Sheets(new \models\drive\Sheets);
            $row = $service->getByGuid($this->getParam(2));
            // If the user is logged and he is the owner
            if ($user_id && $row['user_id'] === $user_id) {
                $share = new \services\drive\Share($this->getParam(2));
                return $share->invite($this->getPost('users'));
            }
        }
    }

    /**
     * Configuration to be sent to the frontend to load the intrasheets
     */
    public function config()
    {
        $token = null;

        $jwt = new \bossanova\Jwt\Jwt;

        $service = new \services\drive\Sheets(new \models\drive\Sheets);
        $service->user_id = $this->getUser();

        // Request invitation token
        $service->token = $this->getParam(2);

        // If the user request access to a spreadsheet by guid
        if ($guid = $this->getParam(1)) {
            // There is no valid access to access this resource
            if ($service->getAccess($guid) === false) {
                header('HTTP/1.0 403 Forbidden');
                die("<h1>Forbidden</h1><p>You don't have permission to access the request resource.</p>");
            } else {
                // Process invitation token
                if ($this->getParam(2)) {
                    // Get the information about the guid
                    $row = $service->getByGuid($this->getParam(1));
                    // Generate a invitation token
                    $data = [
                        'sheet_id' => $row['sheet_id'],
                        'small_token' => $service->token,
                    ];
                    $token = $jwt->setToken($data);
                }
            }
        } else {
            if (! $this->getUser()) {
                // The user is not authenticated
                $this->redirect('/sheets/login', 'You must be authenticated to access this page');
            }
        }

        if ($this->getUser()) {
            // If the user is logged get the user jwt
            $token = $jwt->getToken(true);
        }

        // Return token
        $this->view = [
            // Unique spreadsheet identification
            'guid' => $this->getParam(1),
            // JTW of the user
            'token' => $token,
            // Jspreadsheet license. Normally defined on your ENV files
            'license' => $_ENV['LICENSE'],
        ];

        return $this->loadView('config');
    }
}