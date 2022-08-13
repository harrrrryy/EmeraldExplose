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
    public $event_struct;
    private $item_fact;
    private $EMERALD_EXCHANGE_RATE = 20;
    private $GIVE_TNT = 1;
    private $ISWINNER = false;
    private $DURING_GAME = false;
    private $resporn_position;
    private $shuffle_flag;
    

    public function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->item_fact = new ItemFactory();
        $this->event_struct = [ "playerJoin" => new EventStructure(15,0,"サーバーに参加する",-1),
                                "playerToggleSneak" => new EventStructure(1,0,"スニークを実行/解除",100),
                                "playerDropItem" => new EventStructure(2,0,"アイテムをドロップさせる",200),
                                "playerEmote" => new EventStructure(5,0,"エモートを実行する",-1),
                                "playerBedLeave" => new EventStructure(20,5,"ベッドから離れる",-1),
                                "playerJump" => new EventStructure(2,0,"ジャンプする",300),
                                "playerToggleSprint" => new EventStructure(1,0,"ダッシュを実行/解除",100),
                                "playerToggleSwim" => new EventStructure(2,0,"水泳の実行/解除",150),
                                "playerEditBook" => new EventStructure(128,64,"本の編集",-1),
                                "inventoryOpen" => new EventStructure(3,0,"インベントリを開け閉めする",-1),
                                "blockBreak" => new EventStructure(4,0,"ブロックを破壊する",150),
                                "blockPlace" => new EventStructure(3,0,"ブロックを設置する",150)];
    }


    public function giveEmerald(Player $p, EventStructure $es): bool
    {
        if(!$this->DURING_GAME)
        {
            return false;
        }

        if($es->get_emerald_times != -1 
           && $es->get_emerald_counter >= $es->get_emerald_times)
        {
            return false;
        }

        $item = VanillaItems::EMERALD();
        $inventory = $p->getInventory();
        for($i = 1; $i <= $es->can_get_emerald; $i++)
        {
            if($inventory->canAddItem($item))
            {
                $inventory->addItem($item);
            }
        }

        if($es->get_emerald_times != -1)
        {
            ++$es->get_emerald_counter;            
        }
        return true;
    }

    public function gameEnd(Player $p = NULL, Position $pos = NULL)
    {
        if(!is_null($p))
        {
            Server::getInstance()->getLogger()->info("Finish! \n §5WINNER : §2".$p->getName());
            $p->sendTitle("§lFinish!!!!");
            $p->sendTitle("§l§5WINNER : §l§2".$p->getName());
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
                $player->getInventory()->clearAll();
            }
        }
        return true;
    }

    public function shuffle(): bool
    {
        $this->shuffle_flag = true;
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
                $this->shuffle();
                return true;
            case "outputshuffle":
                if(!$this->shuffle_flag)
                {
                    return false;
                }
                foreach($this->event_struct as $key => $value)
                {
                    if($this->event_struct[$key]->can_get_emerald != 0)
                    {
                        $s->sendMessage($this->event_struct[$key]->explanation.": ".strval($this->event_struct[$key]->can_get_emerald));
                    }                  
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
                            $s->sendTitle("§l".strval($count));
                            --$count;
                        }
                        else
                        {
                            $s->sendTitle("§l§3game start!");
                            $this->getScheduler()->cancelAllTasks();
                            $this->DURING_GAME = true;
                            $this->ISWINNER = false;
                            $this->resporn_position = $s->getPosition();
                            $this->shuffle();
                            foreach(Server::getInstance()->getOnlinePlayers() as $player)
                            {
                                $player->getInventory()->clearAll();
                            }
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
        if($this->shuffle_flag && $this->DURING_GAME)
        {
            $this->giveEmerald($player, $this->event_struct["playerJoin"]);
        }
    }


    public function afterTogglePlayer(PlayerToggleSneakEvent $event)
    {
        $player = $event->getPlayer();
        if($this->shuffle_flag && $this->DURING_GAME)
        {
            $this->giveEmerald($player, $this->event_struct["playerToggleSneak"]);
        }
    }


    public function playerDropItem(PlayerDropItemEvent $event)
    {
        $player = $event->getPlayer();
        if($this->shuffle_flag && $this->DURING_GAME)
        {
            $this->giveEmerald($player, $this->event_struct["playerDropItem"]);
        }
    }


    public function playerEmote(PlayerEmoteEvent $event)
    {
        $player = $event->getPlayer();
        if($this->shuffle_flag && $this->DURING_GAME)
        {
            $this->giveEmerald($player, $this->event_struct["playerEmote"]);
        }
    }


    public function playerBedLeave(PlayerBedLeaveEvent $event)
    {
        $player = $event->getPlayer();
        if($this->shuffle_flag && $this->DURING_GAME)
        {
            $this->giveEmerald($player, $this->event_struct["playerBedLeave"]);
        }
    }


    public function playerJump(PlayerJumpEvent $event)
    {
        $player = $event->getPlayer();
        if($this->shuffle_flag && $this->DURING_GAME)
        {
            $this->giveEmerald($player, $this->event_struct["playerJump"]);
        }
    }

    public function playerEditBook(PlayerEditBookEvent $event)
    {
        $player = $event->getPlayer();
        if($this->shuffle_flag && $this->DURING_GAME)
        {
            $this->giveEmerald($player, $this->event_struct["playerEditBook"]);
        }
    }


    /*
    あまりにもエメラルド取得スピードが速いのでコメントアウト
    public function playerMove(PlayerMoveEvent $event)
    {
        $player = $event->getPlayer();
        if($this->shuffle_flag && $this->DURING_GAME)
        {
            $this->giveEmerald($player, $this->event_struct["playerMove"]);
        }
    }
    */


    public function playerToggleSprint(PlayerToggleSprintEvent $event)
    {
        $player = $event->getPlayer();
        if($this->shuffle_flag && $this->DURING_GAME)
        {
            $this->giveEmerald($player, $this->event_struct["playerToggleSprint"]);
        }
    }


    public function playerToggleSwim(PlayerToggleSwimEvent $event)
    {
        $player = $event->getPlayer();
        if($this->shuffle_flag && $this->DURING_GAME)
        {
            $this->giveEmerald($player, $this->event_struct["playerToggleSwim"]);
        }
    }

    
    //チェストなどのインベントリを開いたときに実行
    public function openInventory(InventoryOpenEvent $event)
    {
        $player = $event->getPlayer();
        if($this->shuffle_flag && $this->DURING_GAME)
        {
            $this->giveEmerald($player, $this->event_struct["inventoryOpen"]);
        }
    }


    public function BlockBreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        if($this->shuffle_flag && $this->DURING_GAME)
        {
            $this->giveEmerald($player, $this->event_struct["blockBreak"]);
        }
    }

    public function BlockPlace(BlockPlaceEvent $event)
    {
        $player = $event->getPlayer();
        if($this->shuffle_flag && $this->DURING_GAME)
        {
            $this->giveEmerald($player, $this->event_struct["blockPlace"]);
        }
    }
}


class EventStructure
{
    public int $emerald_max;
    public int $emerald_min;
    public string $explanation;
    public int $can_get_emerald;
    public int $get_emerald_times;
    public int $get_emerald_counter;

    function __construct(int $max, int $min, string $explanation, int $upper_limit)
    {
        $this->emerald_max = $max;
        $this->emerald_min = $min;
        $this->explanation = $explanation;
        $this->get_emerald_times = $upper_limit;
        $this->get_emerald_counter = 0;
    }
}