<?php
namespace Legoboy\PlayerHead;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use onebone\economyapi\EconomyAPI;
class Main extends PluginBase implements Listener{
	
	public function onEnable(): void{
		if(!(EconomyAPI::getInstance() instanceof EconomyAPI)){
			$this->getLogger()->critical("EconomyAPI is not installed! Plugin disabled.");
			$this->getServer()->getPluginManager()->disablePlugin($this);
		}
		@mkdir($this->getDataFolder());
		$this->saveDefaultConfig();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if(strtolower($command->getName()) == "head"){
			if(!$sender instanceof Player){
				$sender->sendMessage(TextFormat::RED . "Please execute this command as a player.");
				return true;
			}
			if(!isset($args[0])){
				$sender->sendMessage(TextFormat::GREEN . str_repeat('-', 15));
				$sender->sendMessage(TextFormat::YELLOW . "- /head sell [player] > Sells either the specified player\'s head in your inventory, or all your heads.");
				$sender->sendMessage(TextFormat::YELLOW . "- /head list > Lists all the heads you have.");
				$sender->sendMessage(TextFormat::GREEN . str_repeat('-', 15));
				return true;
			}
			switch(strtolower($args[0])){
				case "sell":
					$head = Item::get(397, 3, 1);
					$inv = $sender->getInventory();
					if(isset($args[1])){
						$killed = $args[1];
						$sold = 0;
						foreach($inv->getContents() as $i => $item){
							if($i->equals($head, true, false) && (strtolower($i->getCustomName()) === strtolower($args[1]))){
								$count = $i->getCount();
								$sender->getInventory()->removeItem($i);
								$sold += $count;
								return true;
							}
						}
						if($sold <= 0){
							$sender->sendMessage(TextFormat::RED . "You don't have any of" . TextFormat::RED . $killed . TextFormat::RED . "'s heads!");
							return true;
						}
						$killedMoney = EconomyAPI::getInstance()->myMoney($killed);
						$sender = $sender->getName();
						$earned = round($killedMoney($this->getConfig()->get("heads-value-percentage", 0.1)) * $sold);
						EconomyAPI::getInstance()->addMoney($sender, $earned, true, 'PLUGIN');
						$sender->sendMessage(TextFormat::GREEN . "You sold " . TextFormat::AQUA . $killed . TextFormat::GREEN . "'s head and earned $" . TextFormat::AQUA . $earned);
						return true;
					}else{
						$sold = 0;
						$value = 0;
						foreach($inv->getContents() as $i => $item){
							if($i->equals($head, true, false)){
								$count = $i->getCount();
								$value += round(EconomyAPI::getInstance()->myMoney($i->getCustomName()) === $this->getConfig()->get("heads-value-percentage", 0.1));
								$sold += $count;
								$sender = $sender->getName();
								$sender->getInventory()->removeItem($i);
								return true;
							}
						}
						EconomyAPI::getInstance()->addMoney($sender, $value, true, "PLUGIN");
						$sender->sendMessage(TextFormat::GREEN . "You sold " . TextFormat::AQUA . $sold . TextFormat::GREEN . "heads and earned $" . TextFormat::AQUA . $value . TextFormat::GREEN . "!");
						return true;
					}
					return true;
				case "list":
					$list = [];
					$value = [];
					$head = Item::get(397, 3, 1);
					$inv = $sender->getInventory();
					foreach($inv->getContents() as $i => $item){
						if($i->equals($head, true, false)){
							$killed = $i->getCustomName();
							if(!isset($list[$killed])) $list[$killed] = 0;
							if(!isset($value[$killed])) $value[$killed] = 0;
							$count = $i->getCount();
							$list[$killed] += $count;
							$value[$killed] += round($count * EconomyAPI::getInstance()->myMoney($killed) * $this->getConfig()->get("heads-value-percentage"));
							return true;
						}
					}
					$sender->sendMessage(TextFormat::GOLD . str_repeat('-', 15));
					foreach($list as $name => $count){
						$v = $value[$name];
						$sender->sendMessage(TextFormat::AQUA . $name . "'s head: " . TextFormat::AQUA . $count . TextFormat::GREEN . " with value of $" . TextFormat::AQUA . $v);
						return true;
					}
					$sender->sendMessage(TextFormat::GOLD . str_repeat('-', 15));
					return true;
					break;
			}
		}
	}
	
	public function onDeath(PlayerDeathEvent $event){
		if($this->getConfig()->get("heads-active", true) === true){
			$entity = $event->getEntity();
			$cause = $entity->getLastDamageCause();
			if($cause instanceof EntityDamageByEntityEvent){
				$killer = $cause->getDamager();
				$kName = $killer->getName();
				if(!($killer instanceof Player)) return true;
				$head = Item::get(397, 3, 1);
				$head->setCustomName($entity->getName());
				$killer->getInventory()->addItem($head);
				
				$cost = round(EconomyAPI::getInstance()->myMoney($entity) === $this->getConfig()->get("heads-value-percentage", 0.1));
				EconomyAPI::getInstance()->reduceMoney($entity, $cost, true, "PLUGIN");
				$entity->sendMessage(TextFormat::GREEN . "You were killed by " . Textformat::AQUA . $kName . TextFormat::GREEN . ", and lost $" . TextFormat::AQUA . $cost);
				return true;
				if($this->getConfig()->get("heads-place", false)){
				return true;
					
				}
			}
		}
	
	}
}
