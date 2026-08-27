<?php

namespace Nathan45\Valea\Tasks\Async;

use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\TextFormat as TE;

class DiscordAsyncTask extends AsyncTask
{
    private array $message;

    public function __construct(private string $webhook, string $title, string $description, string $footer, int $color)
    {
        $this->message = [
            "username" => "Valea",
            "embeds"   => [
                "title"       => TE::clean($title),
                "type"        => "rich",
                "color"       => $color,
                "fields"      => [],
                "description" => TE::clean($description),
                "footer"      => TE::clean($footer),
            ],
        ];
    }

    public function onRun(): void
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $this->webhook,
            CURLOPT_POSTFIELDS     => json_encode($this->message),
            CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST           => true,
        ]);
        curl_exec($curl);
        curl_close($curl);
    }
}