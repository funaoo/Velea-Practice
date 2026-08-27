<?php

namespace Nathan45\Valea\Discord;

class Message implements \JsonSerializable
{
    protected array $data = [];

    public function setContent(string $content): void
    {
        $this->data["content"] = $content;
    }

    public function getContent(): ?string
    {
        return $this->data["content"] ?? null;
    }

    public function getUsername(): ?string
    {
        return $this->data["username"] ?? null;
    }

    public function setUsername(string $username): void
    {
        $this->data["username"] = $username;
    }

    public function getAvatarURL(): ?string
    {
        return $this->data["avatar_url"] ?? null;
    }

    public function setAvatarURL(string $avatarURL): void
    {
        $this->data["avatar_url"] = $avatarURL;
    }

    public function addEmbed(Embed $embed): void
    {
        $arr = $embed->asArray();
        if (!empty($arr)) {
            $this->data["embeds"][] = $arr;
        }
    }

    public function setTextToSpeech(bool $ttsEnabled): void
    {
        $this->data["tts"] = $ttsEnabled;
    }

    public function jsonSerialize(): mixed
    {
        return $this->data;
    }
}