<?php

namespace isrdxv\practice\arena;

use isrdxv\practice\Loader;
use isrdxv\practice\arena\Arena;
use isrdxv\practice\arena\event\{
  ArenaCreationEvent,
  ArenaDeleteEvent
};

use pocketmine\utils\Config;

class ArenaManager
{
  public const TYPE_DUEL = 1;
  public const TYPE_FFA = 0;
  
  /** @var Arena[] **/
  private array $arenas = [];
  
  public function __construct(Loader $loader)
  {
    foreach(glob($loader->getDataFolder() . "arenas" . DIRECTORY_SEPARATOR . "*.yml") as $file) {
      if (!is_file($file)) {
        $loader->getLogger()->warning("No hay arenas");
        return;
      }
      $config = new Config($file, Config::YAML);
      $this->setArena($config->getAll());
      $loader->getLogger()->warning("Todas las arenas han sido cargadas");
    }
  }
  
  public function getRandomArena(string $mode, int $type, bool $ranked = false): ?Arena
  {
    $arenas = [];
    if (count($this->arenas) === 0) return null;
    
    foreach($this->arenas as $name => $class) {
      if ($class->getMode() === $mode && $class->getModeType() === $type && $class->getRanked() === $ranked)
      {
        array_push($arenas, $class);
      }
    }
    if (count($arenas) === 0) return null;
    return $arenas[array_rand($arenas, 1)];
  }
  
  /** @return Arena|null **/
  public function getArenaByName(string $arenaName): ?Arena
  {
    return $this->arenas[$arenaName] ?? null;
  }
 
 /**
  * It is used for the arena editing system, just change the parameter for an array
  */
  public function setArena(array $data): void
  {
    $this->arenas[$data["world"]] = ($arena = new Arena($data["world"], $data["slots"], $data["mode"], $data["type"], $data["ranked"], $data["type-mode"], $data["spawns"]));
    (new ArenaCreationEvent($arena))->call();
  }
  
  /** @return Arena[] **/
  public function getArenas(): array
  {
    return $this->arenas;
  }
  
  public function deleteArena(string $arenaName): void
  {
    if (!empty($arena = $this->arenas[$arenaName])) {
        unset($arena);
        (new ArenaDeleteEvent($arena))->call();
    }
  }
  
  /** 
   * Define the name of the arena and the class Arena with the data received from the creation of the arena
   */
  public function createArena(?Arena $arena): void
  {
    if ($arena === null) return;
    $this->arenas[$arena->getName()] = $arena;
    (new ArenaCreationEvent($arena))->call();
  }
  
}
