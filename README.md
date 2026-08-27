# ValeaPracticeCore

A practice core plugin for **PocketMine-MP 5.41.0** (API 5.41.0 · Protocol 924).  
Includes duels, FFA, events, rank system, scoreboard, bots, and more.

---

## Requirements

| Requirement | Version |
|-------------|---------|
| PocketMine-MP | 5.41.0 |
| PHP | 8.2+ |
| API | 5.41.0 |

---

## Installation

1. Download the plugin and place the `Valea - PracticeCore` folder inside `/plugins/`.
2. Edit the values in `src/Nathan45/Valea/Utils/Interfaces/IUtils.php` to match your server.
3. Restart the server.

---

## Main Configuration

All static configuration is located in:

```
src/Nathan45/Valea/Utils/Interfaces/IUtils.php
```

### Server

```php
const IP = "play.yourserver.com";      // Public server IP
const LOBBY_WORLD_NAME = "hub";        // Lobby world name

const X_SPAWN = -13;                   // Spawn X coordinate
const Y_SPAWN = 90;                    // Spawn Y coordinate
const Z_SPAWN = 9;                     // Spawn Z coordinate
```

### Ender Pearl

```php
const PEARL_COOLDOWN = 15;             // Cooldown in seconds
```

### Knockback

```php
const KB_X = 0.4;
const KB_Y = 0.4;
const KB_Z = 0.4;
```

### Boxing & Tiebreak

```php
const BOXING_HIT_TO_WIN = 100;         // Hits required to win in Boxing
const TB_ROUND_FOR_WIN  = 5;           // Rounds required to win Tiebreak
```

---

## Required Worlds

The plugin requires the following worlds to be created on the server:

### Lobby
| Constant | World Name |
|----------|------------|
| `LOBBY_WORLD_NAME` | `hub` |

### FFA
| Mode | World Name |
|------|------------|
| NoDeBuff | `Nodebuff` |
| Gapple | `Gapple` |
| Fist | `Fist` |
| Sumo | `Sumo` |
| Rush | `rush` |
| Boxing | `Boxing` |
| Soup | `Soup` |
| Combo | `Combo` |
| Build | `Build` |

### Duels
| Mode | World Name |
|------|------------|
| NoDeBuff | `NodebuffDuels` |
| Gapple | `GappleDuels` |
| Fist | `FistDuels` |
| Sumo | `SumoDuels` |
| Cave | `CaveDuels` |
| Bridge | `BridgeDuels` |
| Boxing | `BoxingDuels` |
| Spleef | `SpleefDuels` |

### Events
| Mode | World Name |
|------|------------|
| NoDeBuff Event | `NoDeBuffEvent` |
| Gapple Event | `GappleEvent` |
| Sumo Event | `SumoEvent` |

> FFA spawns default to X: 256, Y: 70, Z: 256. Change them in `IUtils.php` if needed.

---

## Discord Webhooks

Set your own webhooks in `IUtils.php`:

```php
const BAN_WEBHOOK           = "https://discord.com/api/webhooks/...";
const UNBAN_WEBHOOK         = "https://discord.com/api/webhooks/...";
const RANK_WEBHOOK          = "https://discord.com/api/webhooks/...";
const REPORT_WEBHOOK        = "https://discord.com/api/webhooks/...";
const REPORT_PLAYER_WEBHOOK = "https://discord.com/api/webhooks/...";
const SKIN_WEBHOOK          = "https://discord.com/api/webhooks/...";
```

---

## Ranks

Ranks are assigned with `/rank <player> <rank>`.

| ID | Rank | Chat Tag |
|----|------|----------|
| 0 | Default | `Default` |
| 1 | Valea+ | `[Valea+]` |
| 2 | YouTube | `[YouTube]` |
| 3 | Helper | `[Helper]` |
| 4 | TMod | `[TMod]` |
| 5 | Mod | `[Mod]` |
| 6 | SrMod | `[SrMod]` |
| 7 | Admin | `[Admin]` |
| 8 | Developer | `[Developer]` |
| 9 | Manager | `[Manager]` |
| 10 | Owner | `[Owner]` |
| 11 | Builder | `[Builder]` |

---

## Commands

### Players
| Command | Description |
|---------|-------------|
| `/spawn` `/lobby` `/hub` | Teleport to the lobby |
| `/autosprint` | Toggle autosprint |
| `/ping` | Check your ping |
| `/profile` | View your profile |
| `/online` | See online players |
| `/info` | Server information |
| `/rules` | View server rules |
| `/report <player>` | Report a player |
| `/inventory <player>` | View another player's inventory |
| `/rekit` | Re-equip your current kit |
| `/event` | Join the active event |

### Staff
| Command | Permission | Description |
|---------|------------|-------------|
| `/ban <player> <reason>` | `valea.staff.ban` | Ban a player |
| `/unban <player>` | `valea.staff.unban` | Unban a player |
| `/freeze <player>` | `valea.staff.freeze` | Freeze a player |
| `/mute <player>` | `valea.staff.mute` | Mute a player |
| `/fly` | `valea.staff.fly` | Toggle flight |
| `/vanish` | `valea.vanish` | Toggle vanish |
| `/who` | `valea.staff.who` | View connected IPs |
| `/me <text>` | `valea.staff.me` | Send an action message |
| `/rank <player> <rank>` | `valea.staff.rank` | Assign a rank |
| `/clearskin <player>` | `valea.staff.clearskin` | Clear a player's skin |
| `/buildperms` | `valea.build` | Toggle build permissions |
| `/editrank` | `valea.rank.edit` | Rank editor |
| `/bot` | `valea.staff.bot` | Spawn a practice bot |
| `/event` (host) | `op` | Manage events |

---

## Permissions

```yaml
# Players (default: true)
valea.command.autosprint
valea.command.info
valea.command.inventory
valea.command.online
valea.command.ping
valea.command.profile
valea.command.rekit
valea.command.report
valea.command.rules
valea.command.spawn
valea.command.event

# Staff (default: op)
valea.staff.ban
valea.staff.unban
valea.staff.freeze
valea.staff.mute
valea.staff.fly
valea.staff.who
valea.staff.me
valea.staff.rank
valea.staff.clearskin
valea.staff.bot
valea.vanish
valea.build
valea.rank.edit

# Special ranks
rank.v          # Valea+ access
rank.manager    # Manager access
```

---

## Database

The plugin uses **SQLite** by default. The file is generated automatically at:

```
plugins/Valea - PracticeCore/data/players.db
```

No additional configuration required.

---

## Capes

Custom capes are stored in:

```
plugins/Valea - PracticeCore/resources/capes/
```

Included files: `Black.png`, `Blue.png`, `Purple.png`, `Red.png`.  
You can add more in PNG format (64x32 px).

---

## Credits

- **Nathan45** - Original development
- **Funaoo** - Updated to PocketMine-MP 5.41.0
- Discord: https://discord.gg/Xs4YjGy2zr
