<?php

namespace Nathan45\Valea\Utils;

use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\Interfaces\IPermissions;
use pocketmine\player\Player;

class Rank
{
    const RANK_DEFAULT = 0;
    const RANK_VALEA = 1;
    const RANK_YOUTUBE = 2;
    const RANK_BUILDER = 11;

    const RANK_HELPER = 3;
    const RANK_TMOD = 4;
    const RANK_MOD = 5;
    const RANK_SRMOD = 6;
    const RANK_ADMIN = 7;
    const RANK_DEVELOPER = 8;
    const RANK_MANAGER = 9;
    const RANK_OWNER = 10;

    private Loader $plugin;
    private Cache $cache;
    private Utils $utils;

    const PERMISSIONS = [
        self::RANK_VALEA => ["rank.v", "valea.rank.edit"],
        self::RANK_YOUTUBE => ["rank.v"],
        self::RANK_HELPER => ["pocketmine.command.teleport", "pocketmine.command.kick"],
        self::RANK_TMOD => ["pocketmine.command.teleport", "pocketmine.command.kick", IPermissions::BAN, IPermissions::VANISH],
        self::RANK_MOD => ["pocketmine.command.teleport", "pocketmine.command.kick", IPermissions::BAN, IPermissions::VANISH, IPermissions::FREEZE],
        self::RANK_SRMOD => ["pocketmine.command.teleport", "pocketmine.command.kick", IPermissions::BAN, IPermissions::VANISH, IPermissions::UNBAN, IPermissions::FREEZE],
        self::RANK_ADMIN => ["pocketmine.command.teleport", "pocketmine.command.kick", IPermissions::BAN, IPermissions::VANISH, IPermissions::UNBAN, IPermissions::FREEZE],
        self::RANK_DEVELOPER => ["pocketmine.command.teleport", "pocketmine.command.kick", IPermissions::BAN, IPermissions::VANISH, IPermissions::FREEZE],
        self::RANK_MANAGER => ["pocketmine.command.teleport", "pocketmine.command.kick", IPermissions::BAN, IPermissions::VANISH, IPermissions::UNBAN, IPermissions::RANK],
        self::RANK_OWNER => [],
        self::RANK_DEFAULT => [],
        self::RANK_BUILDER => [],
    ];

    public function __construct(private int $id, private ?RPlayer $player = null)
    {
        $this->plugin = Loader::getInstance();
        $this->cache = Cache::getInstance();
        $this->utils = new Utils();
    }

    public function getName(): string
    {
        return match ($this->getId()) {
            self::RANK_VALEA     => "§6Valea+",
            self::RANK_YOUTUBE   => "§dYouTube",
            self::RANK_HELPER    => "§aHelper",
            self::RANK_TMOD      => "§2TMod",
            self::RANK_MOD       => "§3Mod",
            self::RANK_SRMOD     => "§1SrMod",
            self::RANK_ADMIN     => "§cAdmin",
            self::RANK_DEVELOPER => "§dDeveloper",
            self::RANK_MANAGER   => "§4Manager",
            self::RANK_OWNER     => "§4Owner",
            self::RANK_BUILDER   => "§7Builder",
            default              => "§aDefault",
        };
    }

    public function toString(): string
    {
        if ($this->player instanceof Player && $this->player->isNick()) return "§a";

        return $this->getColor() . match ($this->getId()) {
                self::RANK_VALEA => "[§6Valea+§f] §6",
                self::RANK_YOUTUBE => "[§dYouTube§f] §d",
                self::RANK_HELPER => "[Helper] ",
                self::RANK_TMOD => "[TMod] ",
                self::RANK_MOD => "[Mod] ",
                self::RANK_SRMOD => "[SrMod] ",
                self::RANK_ADMIN => "[Admin] ",
                self::RANK_DEVELOPER => "[Developer] ",
                self::RANK_MANAGER => "[Manager] ",
                self::RANK_OWNER => "[Owner] ",
                default => "§a",
            };
    }

    public function getColor(): string
    {
        return match ($this->getId()) {
            self::RANK_HELPER => "§a",
            self::RANK_TMOD => "§2",
            self::RANK_MOD => "§3",
            self::RANK_SRMOD => "§1",
            self::RANK_ADMIN => "§c",
            self::RANK_DEVELOPER => "§d",
            self::RANK_MANAGER, self::RANK_OWNER => "§4",
            default => "§a",
        };
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}