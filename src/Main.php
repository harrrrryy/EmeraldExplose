<?php

declare(strict_types=1);

namespace halinezumi\emeraldExplose;

use pocketmine\plugin\PluginBase;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\entity\Entity;

use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageEvent;

use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;

use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerEmoteEvent;
use pocketmine\event\player\PlayerBedLeaveEvent;
use pocketmine\event\player\PlayerJumpEvent;
use pocketmine\event\player\PlayerToggleSprintEvent;
use pocketmine\event\player\PlayerToggleSwimEvent;
use pocketmine\event\player\PlayerEditBookEvent;

use pocketmine\event\inventory\InventoryOpenEvent;

use pocketmine\world\Position;

use pocketmine\scheduler\TaskScheduler;
use pocketmine\scheduler\Task;
use pocketmine\scheduler\ClosureTask;

use pocketmine\player\Player;

use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;

use pocketmine\Server;

class Main extends PluginBase implements Listener
{
    public $event_array = array("playerJoin",
                                "playerToggleSneak",
                                "inventoryOpen",
                                "blockBreak",
                                "blockPlace",
                                "playerDropItem",
                                "playerEmote",
                                "playerBedLeave",
                                "playerJump",
                                "playerToggleSprint",
                                "playerToggleSwim",
                                "playerEditBook");

    public $event_struct;
    public $array_count = -1;
    private $random_number_dict;
    private $MIN_RANDOM_NUM = 0;
    private $MAX_RANDOM_NUM = 3;
    private $item_fact;
    private $EMERALD_EXCHANGE_RATE = 20;
    private $GIVE_TNT = 1;
    private $ISWINNER = false;
    private $DURING_GAME = false;
    private $resporn_position;
    

    public function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->item_fact = new ItemFactory();
        $this->event_struct = [ "playerJoin" => new EventStructure(15,0,"サーバーに参加する"),
                                "playerToggleSneak" => new EventStructure(1,0,"スニークを実行/解除"),
                                "inventoryOpen" => new EventStructure(3,0,"インベントリを開け閉めする"),
                                "blockBreak" => new EventStructure(4,0,"ブロックを破壊する"),
                                "blockPlace" => new EventStructure(3,0,"ブロックを設置する"),
                                "playerDropItem" => new EventStructure(2,0,"アイテムをドロップさせる"),
                                "playerEmote" => new EventStructure(5,0,"エモートを実行する"),
                                "playerBedLeave" => new EventStructure(20,5,"ベッドから離れる"),
                                "playerJump" => new EventStructure(2,0,"ジャンプする"),
                                "playerToggleSprint" => new EventStructure(1,0,"ダッシュを実行/解除"),
                                "playerToggleSwim" => new EventStructure(2,0,"水泳の実行/解除"),
                                "playerEditBook" => new EventStructure(128,64,"本の編集")];
    }


    public function giveEmerald(Player $p, int $num): bool
    {
        if(!$this->DURING_GAME)
        {
            return false;
        }

        $item = VanillaItems::EMERALD();
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

    public function gameEnd(Player $p = NULL, Position $pos = NULL)
    {
        if(!is_null($p))
        {
            Server::getInstance()->getLogger()->info("Finish! \n §5WINNER : §2".$p->getName());
            $p->sendMessage("§lFinish!!!!");
            $p->sendMessage("§l§5WINNER : §l§2".$p->getName());
        }
        else
        {
            echo "game_end command was executed";
        }
        $this->DURING_GAME = false;
        
        if(!is_null($pos))
        {
            foreach(Server::getInstance()->getOnlinePlayers() as $player)
            {
                $player->teleport($pos);
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

        foreach($this->event_struct as $key => $value)
        {
            $this->event_struct[$key]->can_get_emerald = mt_rand($this->event_struct[$key]->emerald_min, $this->event_struct[$key]->emerald_max);
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

                foreach($this->event_struct as $key => $value)
                {
                    $s->sendMessage($this->event_struct[$key]->explanation.": ".strval($this->event_struct[$key]->can_get_emerald));
                }
                return true;
            case "exchange_tnt":
                if(!$this->DURING_GAME)
                {
                    return false;
                }

                $counter = 0;
                //getメソッドの引数はID,META、個数の順
                $emerald = $this->item_fact->get(388, 0, $this->EMERALD_EXCHANGE_RATE);
                $inventory = $s->getInventory();

                while(true)
                {
                    if($inventory->contains($emerald))
                    {
                        ++$counter;
                        $inventory->removeItem($emerald);   

                    }
                    else
                    {
                        break;
                    }
                }

                //TNTを与えるときのみ火打石を1個与える
                if($counter != 0)
                {
                    $flint_and_steel = VanillaItems::FLINT_AND_STEEL();     
                    if($inventory->canAddItem($flint_and_steel))
                    {
                        $inventory->addItem($flint_and_steel);
                    }
                }

                //TNTをエメラルドの個数に応じて与える
                for($i = 1; $i <= $counter; $i++)
                {
                    $tnt = $this->item_fact->get(ItemIds::TNT, 0, $this->GIVE_TNT);
                    if($inventory->canAddItem($tnt))
                    {
                        $inventory->addItem($tnt);
                    }
                } 
                return true;
            case "pos":
                $position = $s->getPosition();
                $s->sendMessage("(x,y,z)=(".strval($position->x).", ".strval($position->y).", ".strval($position->z).")");
                return true;
            case "game_start":
                $count = 3;
                $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(
                    function() use ($s, &$count) : void
                    {
                        if($count >= 1)
                        {
                            $s->sendMessage("§l".strval($count));
                            --$count;
                        }
                        else
                        {
                            $s->sendMessage("§l§3game start!");
                            $this->getScheduler()->cancelAllTasks();
                            $this->DURING_GAME = true;
                            $this->ISWINNER = false;
                            $this->resporn_position = $s->getPosition();
                            $this->array_count = count($this->event_array);
                            $this->shuffle($this->array_count);
                            return;
                        }
                    }
                ), 20);
                return true;
            case "game_end":
                $this->gameEnd(null, $this->resporn_position);
        }
        return true;
    }
   
    public function afterDeath(PlayerDeathEvent $event)
    {
        $player = $event->getPlayer();
        $death_cause = $player->getLastDamageCause();
        //TNTが死因の時はCAUSE_BLOCK_EXPLOSIONではなくCAUSE_ENTITY_EXPLOSIONを使う
        if($death_cause->getCause() == EntityDamageEvent::CAUSE_ENTITY_EXPLOSION 
            && !$this->ISWINNER
            && !is_null($this->resporn_position))
        {
            $this->ISWINNER = true;
            $this->gameEnd($player, $this->resporn_position);
        }
    }

    public function onJoinPlayer(PlayerJoinEvent $event)
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


    public function playerDropItem(PlayerDropItemEvent $event)
    {
        $player = $event->getPlayer();
        if($this->array_count != -1)
        {
            $this->giveEmerald($player, $this->random_number_dict["playerDropItem"]);
        }
    }


    public function playerEmote(PlayerEmoteEvent $event)
    {
        $player = $event->getPlayer();
        if($this->array_count != -1)
        {
            $this->giveEmerald($player, $this->random_number_dict["playerEmote"]);
        }
    }


    public function playerBedLeave(PlayerBedLeaveEvent $event)
    {
        $player = $event->getPlayer();
        if($this->array_count != -1)
        {
            $this->giveEmerald($player, $this->random_number_dict["playerBedLeave"]);
        }
    }


    public function playerJump(PlayerJumpEvent $event)
    {
        $player = $event->getPlayer();
        if($this->array_count != -1)
        {
            $this->giveEmerald($player, $this->random_number_dict["playerJump"]);
        }
    }

    public function playerEditBook(PlayerEditBookEvent $event)
    {
        $player = $event->getPlayer();
        if($this->array_count != -1)
        {
            $this->giveEmerald($player, $this->random_number_dict["playerEditBook"]);
        }
    }


    /*
    あまりにもエメラルド取得スピードが速いのでコメントアウト
    public function playerMove(PlayerMoveEvent $event)
    {
        $player = $event->getPlayer();
        if($this->array_count != -1)
        {
            $this->giveEmerald($player, $this->random_number_dict["playerMove"]);
        }
    }
    */


    public function playerToggleSprint(PlayerToggleSprintEvent $event)
    {
        $player = $event->getPlayer();
        if($this->array_count != -1)
        {
            $this->giveEmerald($player, $this->random_number_dict["playerToggleSprint"]);
        }
    }


    public function playerToggleSwim(PlayerToggleSwimEvent $event)
    {
        $player = $event->getPlayer();
        if($this->array_count != -1)
        {
            $this->giveEmerald($player, $this->random_number_dict["playerToggleSwim"]);
        }
    }

    
    //チェストなどのインベントリを開いたときに実行
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

    public function BlockPlace(BlockPlaceEvent $event)
    {
        $player = $event->getPlayer();
        if($this->array_count != -1)
        {
            $this->giveEmerald($player, $this->random_number_dict["blockPlace"]);
        }
    }
}


class EventStructure
{
    public int $emerald_max;
    public int $emerald_min;
    public string $explanation;
    public int $can_get_emerald;
    function __construct(int $max, int $min, string $explanation)
    {
        $this->emerald_max = $max;
        $this->emerald_min = $min;
        $this->explanation = $explanation;
    }

}