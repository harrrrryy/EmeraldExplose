<?php

declare(strict_types=1);

namespace halinezumi\giveEmerald;

use pocketmine\plugin\PluginBase;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\block\BlockBreakEvent;


use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;

class Main extends PluginBase implements Listener{

    public function onEnable(): void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onCommand(CommandSender $s, Command $c, $label, array $a): bool{
		$out = "";
		$user = $s->getName();
		switch($label){
            case "test":
                $s->sendMessage("success!!!");
				$emerald = VanillaItems::EMERALD();
                $inventory = $s->getInventory();
            
                if($inventory->canAddItem($emerald)){
                    $inventory->addItem($emerald);
                }
                return true;
				break;
        }
        return true;
    }

    
    public function onJoinPlayer(PlayerJoinEVent $event){
        $player = $event->getPlayer();
        $emerald = VanillaItems::EMERALD();
        $inventory = $player->getInventory();
    
        if($inventory->canAddItem($emerald)){
            $inventory->addItem($emerald);
        }
    }


    public function afterTogglePlayer(PlayerToggleSneakEvent $event){
        $player = $event->getPlayer();
        $emerald = VanillaItems::EMERALD();
        $inventory = $player->getInventory();
    
        if($inventory->canAddItem($emerald)){
            $inventory->addItem($emerald);
        }
    }

    //チェストなどのインベントリを開いたときに実行(プレイヤーインベントリは×)
    public function openInventory(InventoryOpenEvent $event){
        $player = $event->getPlayer();
        $emerald = VanillaItems::EMERALD();
        $inventory = $player->getInventory();
    
        if($inventory->canAddItem($emerald)){
            $inventory->addItem($emerald);
        }
    }

    public function BlockBreak(BlockBreakEvent $event){
        $player = $event->getPlayer();
        $emerald = VanillaItems::EMERALD();
        $inventory = $player->getInventory();
    
        if($inventory->canAddItem($emerald)){
            $inventory->addItem($emerald);
            var_dump("BlockBreakEvent:execute");
        }
    }

}
