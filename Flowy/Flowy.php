<?php
namespace Flowy;

use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginManager;
use pocketmine\event\Listener;
use pocketmine\event\RegisteredListener;
use pocketmine\event\EventPriority;
use pocketmine\event\HandlerList;
use pocketmine\event\MethodEventExecutor;
use pocketmine\plugin\PluginException;
use pocketmine\timings\TimingsHandler;

if(!class_exists('Flowy\Flowy')){

abstract class Flowy extends PluginBase implements Listener{

	public const VERSION = '0.1.0';
	
	private $flow_list = [];
	private $registered_listeners = [];

	final protected function start(\Generator $flow){
		$this->initFlow($flow);
		$this->flow_list[] = $flow;
	}

	private function initFlow(\Generator $flow){
		if(!$flow->valid() || $flow->current() === null)
			throw new FlowyException("フローは既に終了しています");
		$this->initAwaitable($flow->current());
	}

	private function initAwaitable($awaitable){
		if(!($awaitable instanceof Awaitable))
			throw new FlowyException("フローからAwaitable以外の値が返されました");
		if($awaitable instanceof ListenAwaitable){
			foreach($awaitable->targets as $event){
				$this->registerEvent($event);
			}
		}
		else if($awaitable instanceof DelayAwaitable){
			$this->scheduleDelay($awaitable);
		}
		else{
			throw new FlowyException("サポートしていないAwaitable派生型がフローから返却されました");
		}
	}

	private function registerEvent(string $event){
		if(isset($this->registered_listeners[$event])){
			$this->registered_listeners[$event]['count'] += 1;
		}
		else{
			$plugin_info = "Plugin: " . $this->getDescription()->getFullName();
			$event_info = "Event: Flowy\Flowy::handleEvent(" . (new \ReflectionClass($event))->getShortName() . ")";
			$timings = new TimingsHandler("{$plugin_info} {$event_info}", PluginManager::$pluginParentTimer);#Timingsに関しては後で見直した方がいい
			$listener = new RegisteredListener($this, new MethodEventExecutor("handleEvent"), EventPriority::NORMAL, $this, false, $timings);
			$this->getEventListeners($event)->register($listener);
			$this->registered_listeners[$event] = [ 'listener' => $listener, 'count' => 1 ];
		}
	}

	private function unregisterEvent(string $event){
		if(!isset($this->registered_listeners[$event]))
			throw new FlowyException("あっれ、解除しようとしたイベントが登録されてない… => {$event}");
		$this->registered_listeners[$event]['count'] -= 1;
		if($this->registered_listeners[$event]['count'] <= 0){
			$this->getEventListeners($event)->unregister($this->registered_listeners[$event]['listener']);
			$this->registered_listeners[$event]['listener'] = null;//なんか不安だからnull入れてるけど要らないかもしれない
			unset($this->registered_listeners[$event]);
		}
	}

	private function getEventListeners(string $event){
		$list = HandlerList::getHandlerListFor($event);
		if($list === null){
			throw new PluginException("待機することができないイベントが渡されました => {$event}");
		}
		return $list;
	}

	private function scheduleDelay(DelayAwaitable $awaitable){
		$awaitable->setTaskId($this->getServer()->getScheduler()->scheduleDelayTask(new FlowyDelayTask($this), $awaitable->getDelay())->getTaskId());
	}

	private function cancelDelay(DelayAwaitable $awaitable){
		$this->getServer()->getScheduler()->cancelTask($awaitable->getTaskId());
	}

	final public function handleEvent($event){
		$class = get_class($event);
		foreach($this->flow_list as $key => $flow){
			$awaitable = $flow->current();
			if(!$awaitable instanceof ListenAwaitable){
				continue;
			}
			if(in_array($class, $awaitable->targets) && $this->filterAll($awaitable->filters, $event)){
				$this->unregisterEvent($class);
				$return = $flow->send($event);
				if($flow->valid()){
					$this->initAwaitable($return);
				}
				else{
					unset($this->flow_list[$key]);
				}
			}
		}
	}

	private function filterAll($filters, $event){
		foreach($filters as $filter){
			if($filter($event) === false){
				return false;
			}
		}
		return true;
	}
}

}// class_exists