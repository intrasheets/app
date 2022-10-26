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
            return $service->insert($data);

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
            $data = parent::login();

            // If all success create an API signature as default
            if (isset($data['id']) && $data['id']) {
                $data['url'] = '/sheets/key';
            }

            return $data;
        }
    }

    /**
     * Generate a new API signature for the user
     * @return array|false
     */
    public function key()
    {
        // Get the logged userId
        if (! $user_id = $this->getUser()) {
            return false;
        }

        $service = new \services\Users(new \models\Users);
        $data = $service->generateKey($user_id);

        if ($this->isAjax()) {
            return $data;
        } else {
            $this->redirect('/sheets/profile');
        }
    }

    /**
     * Profile updates and API Key generation
     */
    public function profile()
    {
        // Get the logged userId
        if (! $user_id = $this->getUser()) {
            return false;
        }

        if ($this->isAjax()) {
            // Process any request to the profile
            $service = new \services\Users(new \models\Users);
            $service->permissions = new \models\Permissions;
            // Process the restful request
            return $this->processRestRequest($service, $user_id);
        } else {
            $this->setView('profile');
        }
    }

    /**
     * Method to notify users when they are invite to share a spreadsheet.
     * This method is necessary because the API do not send emails
     */
    public function notify()
    {
        // Get the logged userId
        if (! $user_id = $this->getUser()) {
            return false;
        }

        // Get the information about the guid
        $service = new \services\drive\Sheets(new \models\drive\Sheets);
        $row = $service->getByGuid($this->getParam(2));
        // If the user is logged and he is the owner
        if ($user_id && $row['user_id'] === $user_id) {
            $share = new \services\drive\Share($this->getParam(2));
            return $share->invite($this->getPost('users'));
        }
    }

    /**
     * Configuration to be sent to the frontend to load the intrasheets
     */
    public function config()
    {
        // Authentication
        $bearer = null;
        // JWT class
        $jwt = new \bossanova\Jwt\Jwt();
        // Intrasheets API
        $intrasheets = new \services\Intrasheets;

        // If the user request access to a spreadsheet by guid
        if ($guid = $this->getParam(1)) {
            // Process the invitation token
            if ($invitationToken = $this->getParam(2)) {
                // Get a validation from the API
                $invitation = $intrasheets->validateInvitation($invitationToken, $this->getUser(), $guid);
                // If validation is OK
                if ($invitation && isset($invitation['success'])) {
                    // If no bound userId or userId different from the API generate a invitation signature
                    if (! $this->getUser()) {
                        $jwt->sheet_id = $invitation['data']['sheet_id'];
                        $jwt->small_token = $invitationToken;
                        $bearer = $jwt->save();
                    }
                }
            }

            // If no authentication and the user is logged use the cookie to get the spreadsheet information
            if ($this->getUser()) {
                $bearer = $jwt->getToken(true);
            }

            // Get the spreadsheet information
            $result = $intrasheets->getSpreasdheet($guid, $bearer);

            // There is no valid access to access this resource
            if (! $result || isset($result['error'])) {
                header('HTTP/1.0 403 Forbidden');
                die("<h1>Forbidden</h1><p>You don't have permission to access the request resource.</p>");
            }
        } else {
            if ($this->getUser()) {
                $bearer = $jwt->getToken(true);
            } else {
                // The user is not authenticated
                $this->redirect('/sheets/login', 'You must be authenticated to access this page');
            }
        }

        // Return token
        $this->view = [
            // Unique spreadsheet identification
            'guid' => $this->getParam(1),
            // JTW of the user
            'token' => $bearer,
            // Jspreadsheet license. Normally defined on your ENV files
            'license' => $_ENV['LICENSE'],
        ];

        return $this->loadView('config');
    }
}