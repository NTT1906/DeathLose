<?php
declare(strict_types=1);
namespace deathlose;

use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;

class Main extends PluginBase implements Listener{
	/** @var Main $instance */
	private static $instance;
	/** @var EconomyAPI $economyapi */
	public $economyapi = null;

	public function onLoad(){
		 self::$instance = $this;
	}
	
	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getLogger()->notice("§fDeath§eLose §fis enabled in background.");
		if($this->economyapi = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI") === null){
			$this->getLogger()->notice("EconomyAPI plugin is not installed, we're installing it for you.");
			$this->download("https://poggit.pmmp.io/get/EconomyAPI", $this->getServer()->getDataPath() . "plugins/", "EconomyAPI"); 
			Server::getInstance()->getPluginManager()->loadPlugin($this->path);
			$this->economyapi = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
		}
		$config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		if($config->get("percent") === null){
			$result = $this->download("https://raw.githubusercontent.com/NTT1906/InstantPotion/master/resources/config.yml", $this->getDataFolder(), "config.yml");
			if($result === false) $config = new Config($this->getDataFolder() . "config.yml", Config::YAML, ["percent" => 25, "message" => "You have been subtraczted {money} cents."]);
		}
		$this->config = $config;
	}

	public function download(string $link, string $path, string $name) : bool{
		$key = ["ssl" => ["verify_peer" => false, "verify_peer_name" => false]];
		$data = file_get_contents($link, false, stream_context_create($key));
		if($data !== false) if(file_put_contents($path . $name) !== false) return true;
		return false;
	}

	public function onDamage(EntityDamageEvent $event){
		$entity = $event->getEntity();

		if($entity instanceof Player){
			if($entity->getHealth() <= $event->getFinalDamage()){
				$data = $this->economyapi->myMoney($entity);

				if($data > 0){
					$percent = (float) $this->config->get("percent") / 100;
					if($percent > 1) $percent = 25 / 100;
					$subtract = $data * percent;
					$base_message = $this->config->getMessage();
					$message = str_replace("{money}", $subtract, $base_message);

					$this->economyapi->reduceMoney($entity, $subtract);
					$entity->sendMessage($message);
				}
			}
		}
	}

	public function onDisable(){
		$this->getLogger()->notice("§fDeath§eLose §fis disabled in background.");
	}
}