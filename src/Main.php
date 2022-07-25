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

use pocketmine\player\Player;

use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;

class Main extends PluginBase implements Listener
{

    public function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function giveEmerald(Player $p, int $num): bool
    {
        $item=VanillaItems::EMERALD();
        $inventory = $p->getInventory();
        for($i = 1; $i <= $num; $i++)
        {
            if($inventory->canAddItem($item))
            {
                $inventory->addItem($item);
            }
        }
        return true;
    }

    public function onCommand(CommandSender $s, Command $c, $label, array $a): bool
    {
		$out = "";
		$user = $s->getName();
		switch($label)
        {
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

    public function onJoinPlayer(PlayerJoinEVent $event)
    {
        $player = $event->getPlayer();
        $this->giveEmerald($player, 2);
    }


    public function afterTogglePlayer(PlayerToggleSneakEvent $event)
    {
        $player = $event->getPlayer();
        $this->giveEmerald($player, 2);
    }

    //チェストなどのインベントリを開いたときに実行(プレイヤーインベントリは×)
    public function openInventory(InventoryOpenEvent $event)
    {
        $player = $event->getPlayer();
        $this->giveEmerald($player, 2);
    }

    public function BlockBreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        if($this->giveEmerald($player, 2))
        {
            var_dump("BlockBreakEvent:execute");
        }
    }
}
