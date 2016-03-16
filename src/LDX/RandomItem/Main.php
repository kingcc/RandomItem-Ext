<?php

namespace LDX\RandomItem;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\item\Item;

class Main extends PluginBase {

  public function onEnable() {
    $this->saveDefaultConfig();
    $c = $this->getConfig()->getAll();
    $t = $c["Interval"] * 1200;
    $num = 0;
    foreach ($c["Items"] as $i) {
      $r = explode(":",$i);
      $this->itemdata[$num] = array("id" => $r[0],"meta" => $r[1],"amount" => $r[2]);
      $num++;
    }
    $this->getServer()->getScheduler()->scheduleRepeatingTask(new Gift($this),$t);
  }

  public function onCommand(CommandSender $issuer,Command $cmd,$label,array $args) {
    if((strtolower($cmd->getName()) == "gift") && isset($args[0])) {
      if($this->getServer()->getPlayer($args[0]) instanceof Player) {
        $d = $this->generateData();
        $p = $this->getServer()->getPlayer($args[0]);
        $this->give($p,$d);
        $p->sendMessage("Gift given! (" . $data["id"] . ":" . $data["meta"] . ")");
      } else {
        $issuer->sendMessage("Player not connected.");
      }
      return true;
    } else if((strtolower($cmd->getName()) == "gift") && !(isset($args[0]))) {
      if ($issuer->hasPermission("randomitem.gift")) {
        $this->giveAll();
      }else{
        $issuer->sendMessage("You haven't access");
      }
      return true;
    } else if (strtolower($cmd->getName()) == "giftcode") {
      $this->saveDefaultConfig();
      $mc = $this->getConfig()->getAll();
      if ($args[0] != "set") {
          $mre = 0;
          foreach ($mc["GiftCode"] as $i) {
            $mr = explode(":",$i);
            if ($args[0] == $mr[0]) {
              $this->give($issuer,array("id" => $mr[1],"meta" => $mr[2],"amount" => $mr[3]));
              $this->array_remove($mc["GiftCode"], $mre);
              $this->getConfig()->set("GiftCode",$mc["GiftCode"]);
              $this->getConfig()->save();
              $this->broadcast("Gift given by giftcode !");
              return true;
            }
            $mre++;
          }
          $this->broadcast("Giftcode is not found");
          return true;
      }else if ($args[0] == "set") {
        if ($issuer->hasPermission("randomitem.gift")) {
          array_push($mc["GiftCode"],$args[1]); 
          $this->getConfig()->set("GiftCode",$mc["GiftCode"]);
          $this->getConfig()->save();
          $this->broadcast("Giftcode set!");
          return true;
        }
      }
    }else{
      return false;
    }
  }

  public function give($p,$data) {
    if($p instanceof Player && ($p->hasPermission("randomitem") || $p->hasPermission("randomitem.receive"))) {
      $item = new Item($data["id"],$data["meta"],$data["amount"]);
      $p->getInventory()->addItem($item);
    }
  }

  public function giveAll() {
    $data = $this->generateData();
    $this->broadcast("Gift given! (" . $data["id"] . ":" . $data["meta"] . ")");
    foreach($this->getServer()->getOnlinePlayers() as $p) {
      $this->give($p,$data);
    }
  }

  public function broadcast($m) {
    foreach($this->getServer()->getOnlinePlayers() as $p) {
      $p->sendMessage($m);
    }
    $this->getLogger()->info(TextFormat::YELLOW . $m);
  }

  public function generateData() {
    return $this->itemdata[mt_rand(0,(count($this->itemdata) - 1))];
  }

  public function array_remove(&$arr, $offset) { 
    array_splice($arr, $offset, 1); 
  } 

}