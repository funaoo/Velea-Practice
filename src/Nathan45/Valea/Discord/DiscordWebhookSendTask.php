<?php

namespace Nathan45\Valea\Discord;

use pocketmine\scheduler\AsyncTask;

class DiscordWebhookSendTask extends AsyncTask
{
    private string $url;
    private string $payload;

    public function __construct(Webhook $webhook, Message $message)
    {
        $this->url = $webhook->getURL();
        $this->payload = json_encode($message);
    }

    public function onRun(): void
    {
        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->payload);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        $this->setResult([curl_exec($ch), curl_getinfo($ch, CURLINFO_RESPONSE_CODE)]);
        curl_close($ch);
    }

    public function onCompletion(): void
    {
        $response = $this->getResult();
        if (!in_array($response[1], [200, 204])) {
            \pocketmine\Server::getInstance()->getLogger()->error("[DiscordWebhookAPI] Got error ({$response[1]}): " . $response[0]);
        }
    }
}