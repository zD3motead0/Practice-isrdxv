<?php
declare(strict_types=1);

namespace isrdxv\practice\session;

use pocketmine\utils\TextFormat;
use pocketmine\player\Player;
use pocketmine\permission\{
  Permission,
  PermissionManager
};
use pocketmine\item\{
  Item,
  ItemIds,
  ItemIdentifier
};

use isrdxv\practice\Loader;
use isrdxv\practice\translation\TranslationMessage;
use isrdxv\practice\provider\YAMLProvider;
use isrdxv\practice\queue\Queue;
use isrdxv\practice\game\Game;

use libs\scoreboard\type\LobbyScoreboard;
use libs\scoreboard\Scoreboard;

class Session
{
  
  private Player $player;
  
  private string $rank;
  
  private string $language;
  
  private string $device;
  
  /** @var Scoreboard|null **/
  private ?Scoreboard $scoreboard;
  
  /** @var Queue|null **/
  private ?Queue $queue = null;
  
  private ?Game $game = null;
  
  private array $settings;
  
  private array $kills;
  
  private array $deaths;
  
  private array $wonEvents;
  
  private int $elo;
  
  public function __construct(Player $player) 
  {
    $this->player = $player;
    $this->scoreboard = new LobbyScoreboard($player);
    // Array
    $this->settings = Loader::getInstance()->getProvider()->loadSettings($player->getName());
    $this->kills = Loader::getInstance()->getProvider()->loadMurders($player->getName());
    $this->deaths = Loader::getInstance()->getProvider()->loadDeaths($player->getName());
    $this->wonEvents = Loader::getInstance()->getProvider()->loadWonEvents($player->getName());
    // Integer
    $this->elo = Loader::getInstance()->getProvider()->loadPoints($player->getName());
    // String
    //$this->rank = Loader::getInstance()->getRankManager()->getRankByName($player->getName());
    $this->device = Loader::getInstance()->getProvider()->getDevice($player->getPlayerInfo()->getExtraData());
    $this->control = Loader::getInstance()->getProvider()->getDeviceControl($player->getPlayerInfo()->getExtraData());
    $this->language = Loader::getInstance()->getProvider()->getLanguage($player->getName());
    $player->setNameTag(TextFormat::AQUA . $player->getName());
    $player->setScoreTag($this->device . " | " . $this->control);
  }
  
  public function getPlayer(): Player
  {
    return $this->player;
  }
  
  public function getDevice(): string
  {
    return $this->device;
  }
  
  public function getScoreboard(): Scoreboard
  {
    return $this->scoreboard;
  }
  
  public function isScoreboard(): bool
  {
    return isset($this->scoreboard);
  }
  
  public function setScoreboard(Scoreboard $scoreboard): void
  {
    if (!$this->getPlayer()->isOnline()) {
      return;
    }
    $this->scoreboard = $scoreboard;
    $scoreboard->show();
    $scoreboard->showLines();
  }
  
  public function changeScoreboard(): void
  {
    if ($this->getSetting("score") === false) {
      $this->getScoreboard()->remove();
      return;
    }
    $this->setScoreboard($this->scoreboard);
  }
  
  public function getQueue(): ?Queue
  {
    return $this->queue ?? null;
  }
  
  public function hasQueue(): bool
  {
    return empty($this->queue) ? false : true;
  }
  
  public function setQueue(?Queue $queue = null): void
  {
    $this->queue = $queue;
  }
  
  public function getGame(): ?Game
  {
    return $this->game ?? null;
  }
  
  public function isGame(): bool
  {
    return empty($this->game) ? false : true;
  }
  
  public function setGame(?Game $game = null): void
  {
    $this->game = $game;
  }
  
  public function getSettings(): array
  {
    return $this->settings;
  }
  
  public function getSetting(string $setting): bool
  {
    return $this->settings[$setting];
  }
  
  public function setSetting(string $setting, bool $value = false): void
  {
    $this->settings[$setting] = $value;
  }
  
  public function setSettings(array $data): void
  {
    $this->settings = $data;
  }
  
  public function getPoints(): int
  {
    return $this->elo;
  }
  
  public function addPoint(int $amount = 0): void
  {
    $this->elo += $amount;
  }
  
  public function subtractPoint(int $amount= 0): void
  {
    $this->elo -= $amount;
  }
  
  public function getMurders(): array
  {
    return $this->kills;
  }
  
  public function getDeaths(): array
  {
    return $this->deaths;
  }
  
  public function getWonEvents(): array
  {
    return $this->wonEvents;
  }
  
  public function getLanguage(): string
  {
    return $this->language;
  }
  
  public function isPlaying(): bool
  {
    if ($this->isGame()) {
      return $this->getGame()->isPlaying($this);
    }
  }
  
  public function sendMessage(TranslationMessage|string $message): void
  {
    if ($message instanceof TranslationMessage) {
      $message = Loader::getInstance()->getTranslation()->addMessage($this->getLanguage(), $message->getText(), $message->getParameters());
    }
    $this->getPlayer()->sendMessage(TextFormat::colorize($message));
  }
  
  public function setGamemode(int $value): void
  {
    switch($value){
      case 0:
        $this->player->setGamemode(GameMode::SURVIVAL());
      break;
      case 1:
        $this->player->setGamemode(GameMode::CREATIVE());
      break;
      case 2:
        $this->player->setGamemode(GameMode::ADVENTURE());
      break;
      case 3:
        $this->player->setGamemode(GameMode::SPECTATOR());
      break;
      default:
        $this->player->setGamemode(GameMode::ADVENTURE());
      break;
    }
  }
  
  /**
   * I'm lazy to shorten the item code
   **/
  public function giveLobbyItems(): void
  {
    $ranked = new Item(new ItemIdentifier(ItemIds::DIAMOND_SWORD, 0));
    $unranked = new Item(new ItemIdentifier(ItemIds::IRON_SWORD, 0));
    $ffa = new Item(new ItemIdentifier(ItemIds::STONE_AXE, 0));
    $party = new Item(new ItemIdentifier(ItemIds::SKULL, 3));
    $profile = new Item(new ItemIdentifier(ItemIds::BOOK, 0));
    $ranked->setCustomName(TextFormat::colorize("&l&fRanked &cQueue"));
    $unranked->setCustomName(TextFormat::colorize("&l&fUnRanked &cQueue"));
    $ffa->setCustomName(TextFormat::colorize("&l&fFFA &cQueue"));
    $party->setCustomName(TextFormat::colorize("&l&fParty"));
    $profile->setCustomName(TextFormat::colorize("&l&fSettings"));
    $this->getPlayer()->getInventory()->clearAll();
    $this->getPlayer()->getArmorInventory()->clearAll();
    $this->getPlayer()->getInventory()->setItem(0, $ranked);
    $this->getPlayer()->getInventory()->setItem(1, $unranked);
    $this->getPlayer()->getInventory()->setItem(2, $ffa);
    $this->getPlayer()->getInventory()->setItem(4, $party);
    $this->getPlayer()->getInventory()->setItem(8, $profile);
  }
  
  public function giveQueueItems(): void
  {
    $leave = new Item(new ItemIdentifier(ItemIds::COMPASS, 0));
    $leave->setCustomName(TextFormat::colorize("&l&o&fLeave Queue"));
    $this->getPlayer()->getInventory()->clearAll();
    $this->getPlayer()->getArmorInventory()->clearAll();
    $this->getPlayer()->getInventory()->setItem(4, $leave);
  }
  
  public function teleportToLobby(): void
  {
    $world = Server::getInstance()->getWorldManager()->getWorldByName(Loader::getInstance()->getConfig()->get("lobby-name"));
    $this->player->teleport($world->getSafeSpawn());
    $this->player->setHealth($this->player->getMaxhealth());
  }
  
  public function addPermission(string $permission = "", string $description = null): void
  {
    if ($permission !== "") {
      $permission = new Permission($permission, $description);
    }
    PermissionManager::getInstance()->addPermission($permission);
  }
  
  public function __toArray(): array
  {
    return [
      "name" => $this->getPlayer()->getName(),
      "points" => $this->getPoints(),
      "device" => $this->getDevice(),
      "points" => $this->getPoints(),
      //"rank" => $this->getRank(),
      "murders" => $this->getMurders(),
      "deaths" => $this->getDeaths(),
      //"language" => $this->getPlayer()->getLocale(),
      "won-events" => $this->getWonEvents(),
    ];
  }
  
}
