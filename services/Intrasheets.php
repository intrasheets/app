<?php

namespace services;

class Intrasheets {

    public $server = null;

    public function __construct()
    {
        $this->server = 'http://web/api';
    }

    public function request($url, $bearer) {
        $headers = [
            'Accept: text/json',
            'Authorization: Bearer ' . $bearer,
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];

        // URL
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_VERBOSE, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($curl);

        if ($response) {
            return json_decode($response, true);
        }

        return false;
    }

    /**
     * Request a new access key to the API
     * @param $user_id
     * @return array
     */
    public function requestKey($user_id)
    {
        // Create JWT token
        $jwt = new \bossanova\Jwt\Jwt();

        // Create bearer for the authentication
        $bearer = $jwt->createToken([
            'exp' => time() + 30,
            'user_id' => $user_id,
            'scope' => ['signature'],
        ]);

        // Call the API to generate a new signature for the defined user
        return $this->request("{$this->server}/signature", $bearer);
    }

    /**
     * Validate an invitation token
     * @param $token - the small token or invitation token
     * @param $user_id - the user which is logged
     * @return array
     */
    public function validateInvitation($token, $user_id=false, $guid)
    {
        // Create JWT token
        $jwt = new \bossanova\Jwt\Jwt();

        // Create bearer for the authentication
        $bearer = $jwt->createToken([
            'exp' => time() + 30,
            'sheet_guid' => $guid,
            'user_id' => $user_id,
            'small_token' => $token,
            'scope' => ['invitation'],
        ]);

        // Call the API to generate a new signature for the defined user
        return $this->request("{$this->server}/invitation", $bearer);
    }


    /**
     * Get spreadsheet
     * @param $guid - spreadsheet identification
     * @param $bearer - in case the user is logged
     * @return array
     */
    public function getSpreasdheet($guid, $bearer)
    {
        // Call the API to generate a new signature for the defined user
        return $this->request("{$this->server}/{$guid}/all", $bearer);
    }
}