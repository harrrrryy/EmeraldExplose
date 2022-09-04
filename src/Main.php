<?php

declare(strict_types=1);

namespace halinezumi\emeraldExplose;

use pocketmine\plugin\PluginBase;

use pocketmine\block\VanillaBlocks;

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
use pocketmine\event\player\PlayerBucketFillEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerChangeSkinEvent;

use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\inventory\CraftItemEvent;

use pocketmine\world\Position;

use pocketmine\scheduler\TaskScheduler;
use pocketmine\scheduler\Task;
use pocketmine\scheduler\ClosureTask;

use pocketmine\player\Player;

use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;

use pocketmine\math\Vector3;

use pocketmine\Server;

use pocketmine\World\World;

class Main extends PluginBase implements Listener
{
    public $event_struct;
    private $item_fact;
    private $EMERALD_EXCHANGE_RATE = 64;
    private $GIVE_TNT = 1;
    private $ISWINNER = false;
    private $DURING_GAME = false;
    private $resporn_position;
    private $shuffle_flag;
    private $can_set_block_list;
    private $world;
    private $STAGE_DEPTH = 20;
    private $STAGE_WIDTH = 20;
    private $STAGE_HEIGHT = 3;
    private $stage_endpoint1;
    private $stage_endpoint2;
    private $DELETE_AREA_DEPTH = 175;
    private $DELETE_AREA_WIDTH = 175;
    private $DELETE_AREA_HEIGHT = 5;

    public function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->item_fact = new ItemFactory();
        $this->event_struct = [ "playerJoin" => new EventStructure(15,0,"サーバーに参加する",5),
                                "playerToggleSneak" => new EventStructure(1,0,"スニークを実行/解除",30),
                                "playerDropItem" => new EventStructure(2,0,"アイテムをドロップさせる",20),
                                "playerEmote" => new EventStructure(5,0,"エモートを実行する",15),
                                "playerBedLeave" => new EventStructure(20,5,"ベッドから離れる",-1),
                                "playerJump" => new EventStructure(2,0,"ジャンプする",70),
                                "playerToggleSprint" => new EventStructure(1,0,"ダッシュを実行/解除",30),
                                "playerToggleSwim" => new EventStructure(2,0,"水泳の実行/解除",30),
                                "playerEditBook" => new EventStructure(128,64,"本の編集",-1),
                                "playerBucketFill" => new EventStructure(128,64,"バケツの中を満たす",1),
                                "playerChat" => new EventStructure(5,0,"チャットを行う",20),
                                "playerChangeSkin" => new EventStructure(5,0,"スキンを変える",20),
                                "inventoryOpen" => new EventStructure(3,0,"インベントリを開け閉めする",10),
                                "craftItem" => new EventStructure(2,0,"アイテムをクラフトする",10),
                                "blockBreak" => new EventStructure(4,0,"ブロックを破壊する",20),
                                "blockPlace" => new EventStructure(3,0,"ブロックを設置する",20)];

        $this->can_set_block_list = [VanillaBlocks::EMERALD(),
                                     VanillaBlocks::DIAMOND(),
                                     VanillaBlocks::ACACIA_WOOD(),
                                     VanillaBlocks::BIRCH_WOOD(),
                                     VanillaBlocks::DARK_OAK_WOOD(),
                                     VanillaBlocks::JUNGLE_WOOD(),
                                     VanillaBlocks::OAK_WOOD(),
                                     VanillaBlocks::SPRUCE_WOOD(),
                                     VanillaBlocks::STRIPPED_ACACIA_WOOD(),
                                     VanillaBlocks::STRIPPED_BIRCH_WOOD(),
                                     VanillaBlocks::STRIPPED_DARK_OAK_WOOD(),
                                     VanillaBlocks::STRIPPED_JUNGLE_WOOD(),
                                     VanillaBlocks::STRIPPED_OAK_WOOD(),
                                     VanillaBlocks::STRIPPED_SPRUCE_WOOD(),
                                     VanillaBlocks::CHISELED_RED_SANDSTONE(),
                                     VanillaBlocks::CHISELED_SANDSTONE(),
                                     VanillaBlocks::CUT_RED_SANDSTONE(),
                                     VanillaBlocks::CUT_RED_SANDSTONE_SLAB(),
                                     VanillaBlocks::CUT_SANDSTONE(),
                                     VanillaBlocks::CUT_SANDSTONE_SLAB(),
                                     VanillaBlocks::RED_SAND(),
                                     VanillaBlocks::RED_SANDSTONE(),
                                     VanillaBlocks::RED_SANDSTONE_SLAB(),
                                     VanillaBlocks::SAND(),
                                     VanillaBlocks::SANDSTONE(),
                                     VanillaBlocks::SANDSTONE_SLAB(),
                                     VanillaBlocks::SOUL_SAND(),
                                     VanillaBlocks::BLUE_ICE(),
                                     // 設置してすぐ水になるので保留
                                     //VanillaBlocks::FROSTED_ICE(),
                                     VanillaBlocks::ICE(),
                                     VanillaBlocks::PACKED_ICE(),
                                     VanillaBlocks::STONE(),
                                     VanillaBlocks::COAL_ORE(),
                                     VanillaBlocks::DIAMOND_ORE(),
                                     VanillaBlocks::EMERALD_ORE(),
                                     VanillaBlocks::GOLD_ORE(),
                                     VanillaBlocks::IRON_ORE(),
                                     VanillaBlocks::LAPIS_LAZULI_ORE(),
                                     VanillaBlocks::NETHER_QUARTZ_ORE(),
                                     VanillaBlocks::NETHER_REACTOR_CORE(),
                                     VanillaBlocks::REDSTONE_ORE(),
                                     VanillaBlocks::CRAFTING_TABLE(),
                                     VanillaBlocks::FURNACE(),
                                     VanillaBlocks::GLASS(),
                                     VanillaBlocks::GLOWSTONE(),
                                     VanillaBlocks::BOOKSHELF(),
                                     VanillaBlocks::ENCHANTING_TABLE(),
                                     VanillaBlocks::COBBLESTONE(),
                                     VanillaBlocks::COBBLESTONE_SLAB(),
                                     VanillaBlocks::DIRT(),
                                     VanillaBlocks::GRASS_PATH(),
                                     VanillaBlocks::GRASS(),
                                     VanillaBlocks::GRAVEL()];
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
        $this->getScheduler()->cancelAllTasks();
        if(!is_null($p))
        {
            Server::getInstance()->getLogger()->info("Finish! \n §5WINNER : §2".$p->getName());
            foreach(Server::getInstance()->getOnlinePlayers() as $player)
            {
                $player->sendTitle("§lFinish!!!!");
                $player->sendTitle("§l§5WINNER\n§l§2".$p->getName());
            }     
        }
        else
        {
            foreach(Server::getInstance()->getOnlinePlayers() as $player)
            {
                $player->sendTitle("Time Up!");
                $player->sendMessage("制限時間になったのでゲームを終了しました");
            }
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

        foreach($this->event_struct as $key => $value)
        {
            $this->event_struct[$key]->get_emerald_counter = 0;
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
    
    public function generateStage(Vector3 $vector): bool
    {
        $this->deleteStage();
        if(is_null($this->world))
        {
            $this->world = $vector->getWorld();
        }
        $this ->stage_endpoint1 = new Vector3($vector->x - $this->STAGE_WIDTH, $vector->y, $vector->z - $this->STAGE_DEPTH);
        $this ->stage_endpoint2 = new Vector3($vector->x + $this->STAGE_WIDTH, $vector->y - $this->STAGE_HEIGHT, $vector->z + $this->STAGE_DEPTH);
        for($k = $vector->y; $k > $vector->y - $this->STAGE_HEIGHT; $k--)
        {
            for($i = $vector->z - $this->STAGE_DEPTH; $i <= $vector->z + $this->STAGE_DEPTH; $i++)
            {
                for($j = $vector->x - $this->STAGE_WIDTH; $j <= $vector->x + $this->STAGE_WIDTH; $j++)
                {
                    $this->world->setBlock(new Vector3($j, $k, $i), $this->can_set_block_list[mt_rand(0,count($this->can_set_block_list)-1)]);
                }
            }
        }
        return True;
    }

    public function deleteStage(): bool
    {
        if(is_null($this->stage_endpoint1) || is_null($this->stage_endpoint2) || is_null($this->world))
        {
            return false;
        }
        for($k = $this->stage_endpoint1->y; $k > $this->stage_endpoint2->y; $k--)
        {
            for($i = $this->stage_endpoint1->z; $i <= $this->stage_endpoint2->z; $i++)
            {
                for($j = $this->stage_endpoint1->x; $j <= $this->stage_endpoint2->x; $j++)
                {
                    $this->world->setBlock(new Vector3($j, $k, $i), VanillaBlocks::AIR());
                }
            }
        }
        $this->stage_endpoint1 = NULL;
        $this->stage_endpoint2 = NULL;
        return true;
    }


    public function onCommand(CommandSender $s, Command $c, $label, array $a): bool
    {
		$out = "";
		$user = $s->getName();
		switch($label)
        {
            case "test":
                //$s->sendSubTitle("sendSubTitle");             
                //$s->sendActionBarMessage("sendActionBarMessage");
                //$s->sendPopup("sendPopup");
                //$s->sendTip("sendTip");
                
                //$s->sendMessage("success!!!");
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
                $count_down = 3;
                $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(
                    function() use ($s, &$count_down) : void
                    {
                        if($count_down >= 1)
                        {
                            foreach(Server::getInstance()->getOnlinePlayers() as $player)
                            {
                                $player->sendTitle("§l".strval($count_down));
                            } 
                            --$count_down;
                        }
                        else
                        {                                                     
                            $this->getScheduler()->cancelAllTasks();
                            $this->DURING_GAME = true;
                            $this->ISWINNER = false;
                            if(is_null($this->resporn_position))
                            {
                                $this->resporn_position = $s->getPosition();
                            }
                            $this->shuffle();
                            $this->generateStage($s->getPosition());
                            foreach(Server::getInstance()->getOnlinePlayers() as $player)
                            {
                                $player->sendTitle("§l§3game start!");
                                $player->getInventory()->clearAll();
                            }

                            $timer_count = 300;
                            $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(
                                function() use ($s, &$timer_count) : void
                                {
                                    if($timer_count >= 1)
                                    {
                                        foreach(Server::getInstance()->getOnlinePlayers() as $player)
                                        {
                                            $player->sendActionBarMessage("§l".strval($timer_count));
                                        } 
                                        --$timer_count;
                                    }
                                    else
                                    {
                                        $this->getScheduler()->cancelAllTasks();
                                        $this->gameEnd(null, $this->resporn_position);                                                     
                                        return;
                                    }
                                }
                            ), 20);
                            return;
                        }
                    }
                ), 20);
                return true;
            case "game_end":
                $this->gameEnd(null, $this->resporn_position);
                return true;
            case "tpall":
                $pos = $s->getPosition();
                foreach(Server::getInstance()->getOnlinePlayers() as $player)
                {
                    $player->teleport($pos);
                }
                return true;
            case "generate_stage":
                $this->generateStage($s->getPosition());
                return true;
            // 指定した範囲が広すぎるとメモリ不足でエラー出るので注意
            case "delete_most_blocks":
                $position = $s->getPosition();
                $endpoint1 = new Vector3((int)$position->x - $this->DELETE_AREA_WIDTH, (int)$position->y, (int)$position->z - $this->DELETE_AREA_DEPTH);
                $endpoint2 = new Vector3((int)$position->x + $this->DELETE_AREA_WIDTH, (int)$position->y - $this->DELETE_AREA_HEIGHT, (int)$position->z + $this->DELETE_AREA_DEPTH);
                if(is_null($this->world))
                {
                    $this->world = $position->getWorld();
                }
                for($k = $endpoint1->y; $k > $endpoint2->y; $k--)
                {
                    if($k <= -1)
                    {
                        break;
                    }
                    for($i = $endpoint1->z; $i <= $endpoint2->z; $i++)
                    {
                        for($j = $endpoint1->x; $j <= $endpoint2->x; $j++)
                        {
                            //$this->world->setBlock(new Vector3($j, $k, $i), VanillaBlocks::AIR());
                            $this->world->setBlockAt($j, $k, $i, VanillaBlocks::AIR());
                        }
                    }
                }
                $this->world->setBlockAt((int)$position->x, 100, (int)$position->z, VanillaBlocks::EMERALD());
                $this->resporn_position = new Position((int)$position->x, 100, (int)$position->z, $this->world);
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
        else if(!$this->DURING_GAME)
        {
            $player->getInventory()->clearAll();
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


    public function playerBucketFill(PlayerBucketFillEvent $event)
    {
        $player = $event->getPlayer();
        if($this->shuffle_flag && $this->DURING_GAME)
        {
            $this->giveEmerald($player, $this->event_struct["playerBucketFill"]);
        }
    }


    public function playerChat(PlayerChatEvent $event)
    {
        $player = $event->getPlayer();
        if($this->shuffle_flag && $this->DURING_GAME)
        {
            $this->giveEmerald($player, $this->event_struct["playerChat"]);
        }
    }


    public function playerChangeSkin(PlayerChangeSkinEvent $event)
    {
        $player = $event->getPlayer();
        if($this->shuffle_flag && $this->DURING_GAME)
        {
            $this->giveEmerald($player, $this->event_struct["playerChangeSkin"]);
        }
    }


    public function craftItem(CraftItemEvent $event)
    {
        $player = $event->getPlayer();
        if($this->shuffle_flag && $this->DURING_GAME)
        {
            $this->giveEmerald($player, $this->event_struct["craftItem"]);
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