<?php

namespace services\drive;

use bossanova\Config\Config;
use bossanova\Mail\Mail;

Class Share
{
    public function __construct($guid)
    {
        $this->guid = $guid;
    }

    public function invite($data)
    {
        foreach ($data as $k => $v) {
            $this->notify($v);
        }

        return [
            'success' => 1,
            'message' => 'Sent',
        ];
    }

    private function notify($row)
    {
        if (! isset($this->mail) || ! $this->mail) {
            // Get preferable mail adapter
            $adapter = Config::get('mail');
            // Create instance
            $this->mail = new Mail($adapter);
        }

        // Get content
        $template = file_get_contents('resources/texts/invite.txt');
        $template = $this->mail->translate($template);

        // Replace macros
        $mailTemplate = $this->mail->replaceMacros($template, [
            'url' => BASE . 'sheets/'. $this->guid . '/' . $row['token'],
            'user_email' => $row['email'],
        ]);

        // From
        $f = [ MS_CONFIG_FROM, MS_CONFIG_NAME ];

        // Destination
        $t = [];
        $t[] = [ $row['email'], '' ];

        // Send email
        $this->mail->sendmail($t, 'A JSS spreadsheet has been shared with you', $mailTemplate, $f);
    }
}