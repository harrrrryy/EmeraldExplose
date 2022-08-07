<?php

declare(strict_types=1);

namespace halinezumi\emeraldExplose;

use pocketmine\plugin\PluginBase;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\entity\Entity;

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
    public $event_array = array("playerJoin","playerToggleSneak","inventoryOpen","blockBreak");
    public $array_count = -1;
    private $random_number_dict;
    private $MIN_RANDOM_NUM = 0;
    private $MAX_RANDOM_NUM = 3;
    private $item_fact;


    public function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->item_fact = new ItemFactory();
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


    public function shuffle(int $num): bool
    {
        for($i = 0; $i < $num; $i++)
        {
            $this->random_number_dict[$this->event_array[$i]] = mt_rand($this->MIN_RANDOM_NUM, $this->MAX_RANDOM_NUM);
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
            case "shuffle":
                $this->array_count = count($this->event_array);
                $this->shuffle($this->array_count);
                return true;
            case "outputshuffle":
                if($this->array_count == -1)
                {
                    $s->sendMessage("error: /shuffle is not excuted");
                    return false;
                }
                $this->array_count = count($this->event_array);
                for($i = 0; $i < $this->array_count; $i++)
                {
                    $s->sendMessage($this->event_array[$i].":".strval($this->random_number_dict[$this->event_array[$i]]));
                }
                return true;
            case "exchange_tnt":
                $item = $this->item_fact->get(388, 0, 10);
                //TODO:アイテムを減らすのが上手くいったときだけTNTなどを渡す条件を追加
                $s->getInventory()->removeItem($item);   //getの引数はID,meta,countの順
            case "pos":
                $position = $s->getPosition();
                $s->sendMessage("(x,y,z)=(".strval($position->x).", ".strval($position->y).", ".strval($position->z).")");
        }
        return true;
    }
   

    public function onJoinPlayer(PlayerJoinEVent $event)
    {
        $player = $event->getPlayer();
        if($this->array_count != -1)
        {
            $this->giveEmerald($player, pow($this->random_number_dict["playerJoin"],3));
        }
    }


    public function afterTogglePlayer(PlayerToggleSneakEvent $event)
    {
        $player = $event->getPlayer();
        if($this->array_count != -1)
        {
            $this->giveEmerald($player, $this->random_number_dict["playerToggleSneak"]);
        }
    }


    //チェストなどのインベントリを開いたときに実行(プレイヤーインベントリは×)
    public function openInventory(InventoryOpenEvent $event)
    {
        $player = $event->getPlayer();
        if($this->array_count != -1)
        {
            $this->giveEmerald($player, $this->random_number_dict["inventoryOpen"]);
        }
    }


    public function BlockBreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        if($this->array_count != -1)
        {
            $this->giveEmerald($player, $this->random_number_dict["blockBreak"]);
        }
    }
}
