<?php

namespace modules\Sheets;

use bossanova\Module\Module;

class Sheets extends Module
{
    public function __default()
    {
        // Set the template to be the intrasheets
        $this->setLayout('sheets/index.html');

        // Get the configuration and append to the config in the temmplate
        $area = new \stdClass;
        $area->template_area = 'config';
        $area->module_name = $this->getParam(0);
        $area->method_name = 'config';
        $this->setContent($area);
    }

    public function config()
    {
        $token = null;

        $service = new \services\drive\Sheets(new \models\drive\Sheets);
        $service->user_id = $this->getUser();
        $service->token = $this->getParam(2);

        if ($guid = $this->getParam(1)) {
            if ($service->getAccess($guid) === false) {
                header('HTTP/1.0 403 Forbidden');
                die("<h1>Forbidden</h1><p>You don't have permission to access the request resource.</p>");
            } else {
                if ($this->getParam(2)) {
                    // Get the information about the guid
                    $row = $service->getByGuid($this->getParam(1));
                    $data = [
                        'sheet_id' => $row['sheet_id'],
                        'small_token' => $service->token,
                    ];
                    $jwt = new \bossanova\Jwt\Jwt;
                    $token = $jwt->setToken($data);
                }

                if ($service->user_id) {
                    $jwt = new \bossanova\Jwt\Jwt;
                    $token = $jwt->setToken($jwt->getToken());
                }
            }
        }

        $this->view = $token;

        return $this->loadView('config');
    }

    public function notify()
    {
        // Get the logged userId
        $user_id = $this->getUser();
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