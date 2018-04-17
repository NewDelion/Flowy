<?php
namespace Flowy;

use pocketmine\timings\TimingsHandler;
use pocketmine\plugin\PluginManager;
use pocketmine\plugin\RegisteredListener;
use pocketmine\plugin\MethodEventExecutor;
use pocketmine\plugin\PluginException;
use pocketmine\event\EventPriority;
use pocketmine\event\HandlerList;

if(!class_exists('Flowy\Flowy')){

include_once(__DIR__.'/functions.php');

abstract class Flowy extends PluginBase implements Listener{
	/** @var FlowyObjectMap<\Generator> */
	private $flowMap = new FlowyObjectMap(\Generator::class);

	/** @var FlowyObjectMap<BranchInfo> */
	private $branchInfoMap = new FlowyObjectMap(BranchInfo::class);

	/** @var (RegisteredListener, int)[string] */
	private $registeredListeners = [];

	private function valid(\Generator $flow){
		if(!$flow->valid())
			return false;
		if($flow->current() === null || $flow->current() instanceof FlowDone || $flow->current() instanceof FlowCancel)
			return false;
		if(!($flow->current() instanceof ListenAwaitable))
			return false;
		return true;
	}

	public function start(\Generator $flow, ?int $flowIndex = null){
		if(!($flow->current() instanceof ListenAwaitable))
			throw new FlowyException('');
		if(!$this->valid($flow))
			throw new FlowyException('');
		if(isset($flow->infoIndex))
			throw new FlowyException('');

		foreach($flow->current()->getTargetEvents() as $event){
			$this->registerEvent($event);
		}
		$flow->active = true;
		$flow_index = $flowIndex ?? $this->flowMap->add($flow);
		if(!$flow->current()->hasBranches())
			return $flow_index;

		$info = new BranchInfo();
		$flow->infoIndex = $this->branchInfoMap->add($info);
		$info->setMainFlowIndex($flow_index);
		foreach($flow->current()->getBranches() as list($branchDef, $continueWhenDone)){
			$branchFlow = $branchDef();
			$branchFlow->parentIndex = $flow_index;
			$branchFlow->continueWhenDone = $continueWhenDone;
			foreach($this->startBranch($branchFlow) as $branchIndex){
				$info->addBranchIndex($branchIndex);
			}
		}
		return $flow_index;
	}

	private function startBranch(\Generator $flow){
		if(!($flow->current() instanceof ListenAwaitable))
			throw new FlowyException('');
		if(!$this->valid($flow))
			throw new FlowyException('');

		foreach($flow->current()->getTargetEvents() as $event){
			$this->registerEvent($event);
		}
		$flow->active = true;
		$flow_index = $this->flowMap->add($flow);
		yield $flow_index;
		if(!$flow->current()->hasBranches())
			return;

		foreach($flow->current()->getBranches() as list($branchDef, $continueWhenDone)){
			$branchFlow = $branchDef();
			$branchFlow->parentIndex = $flow_index;
			$branchFlow->continueWhenDone = $continueWhenDone;
			yield from $this->startBranch($branchFlow);
		}
	}

	private function registerEvent(string $event){
		if(isset($this->registeredListeners[$event])){
			$this->registeredListeners[$event]['count'] += 1;
		}
		else{
			$plugin_info = "Plugin: " . $this->getDescription()->getFullName();
			$event_info = "Event: Flowy\Flowy::handleEvent(" . (new \ReflectionClass($event))->getShortName() . ")";
			$timings = new TimingsHandler("{$plugin_info} {$event_info}", PluginManager::$pluginParentTimer);#Timingsに関しては後で見直した方がいい
			$listener = new RegisteredListener($this, new MethodEventExecutor("handleEvent"), EventPriority::NORMAL, $this, false, $timings);
			$this->getEventListeners($event)->register($listener);
			$this->registeredListeners[$event] = [ 'listener' => $listener, 'count' => 1 ];
		}
	}

	private function unregisterEvent(string $event){
		if(!isset($this->registeredListeners[$event]))
			return;
		$this->registeredListeners[$event]['count'] -= 1;
		if($this->registeredListeners[$event]['count'] <= 0){
			$this->getEventListeners($event)->unregister($this->registeredListeners[$event]['listener']);
			$this->registeredListeners[$event]['listener'] = null;//なんか不安だからnull入れてるけど要らないかもしれない
			unset($this->registeredListeners[$event]);
		}
	}

	private function getEventListeners(string $event){
		$list = HandlerList::getHandlerListFor($event);
		if($list === null){
			throw new PluginException("待機することができないイベントが渡されました => {$event}");
		}
		return $list;
	}

	public function handleEvent(Event $event){
		$class = get_class($event);
		foreach($this->flowMap as $flow_index => $flow){
			if(!$flow->active || !in_array($class, $flow->current()->getTargetEvents()) || !$this->filterAll($flow->current()->getFilters()))
				continue;
			if(isset(($root_flow = $this->flowMap[$this->getRootFlowIndex($flow_index)])->infoIndex)){
				$this->inactiveAll($info = $this->branchInfoMap[$root_flow->infoIndex], [ $flow_index ]);
				if($root_flow === $flow){# isset($flow->infoIndex)でもいいはずだよ
					$this->removeBranches($info);
					$this->branchInfoMap->remove($flow->infoIndex);
					unset($flow->infoIndex);
				}
			}
			foreach($flow->current()->getTargetEvents() as $class)
					$this->unregisterEvent($class);
			$this->run($flow_index, $event);
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

	private function getRootFlowIndex(int $currentFlowIndex){
		$flow = $this->flowMap[$currentFlowIndex];
		if(!isset($flow->parentIndex))
			return $currentFlowIndex;
		$parentFlow = $this->flowMap[$flow->parentIndex];
		if(isset($parentFlow->infoIndex))
			return $flow->parentIndex;
		return $this->getRootFlowIndex($flow->parentIndex);
	}

	private function inactiveAll(BranchInfo $info, array $excludeIndexes = []){
		if(!in_array($info->getMainFlowIndex(), $excludeIndexes, true)){
			$main_flow = $this->flowMap[$info->getMainFlowIndex()];
			if($main_flow->active){
				$main_flow->active = false;
				foreach($main_flow->current()->getTargetEvents() as $event)
					$this->unregisterEvent($event);
				$main_flow->current()->handleInactive();
			}
		}
		foreach($info->getBranchFlowIndexes() as $branch_index){
			if(in_array($branch_index, $excludeIndexes, true))
				continue;
			$branch_flow = $this->flowMap[$branch_index];
			if($branch_flow->active){
				$branch_flow->active = false;
				foreach($branch_flow->current()->getTargetEvents() as $event)
					$this->unregisterEvent($event);
				$branch_flow->current()->handleInactive();
			}
		}
	}

	private function removeBranches(BranchInfo $info){
		foreach($info->getBranchFlowIndexes() as $branch_index){
			if(!isset($this->flowMap[$branch_index]))
				continue;
			$branch_flow = $this->flowMap[$branch_index];
			if($branch_flow->active){//アクティブなはずないからthrowでもいいんだけどね
				$branch_flow->active = false;
				foreach($branch_flow->current()->getTargetEvents() as $event)
					$this->unregisterEvent($event);
				$branch_flow->current()->handleInactive();
			}
			$branch_flow = null;
			$this->flowMap->reomve($branch_index);
		}
	}

	private function run(int $flowIndex, ?Event $event){
		$result = ($flow = $this->flowMap[$flowIndex])->send($event);
		if($this->valid($result)){
			$this->start($flow, $flowIndex);
			return;
		}
		$this->flowMap->remove($flowIndex);
		if($result === null && $result instanceof FlowDone){
			if(isset($flow->continueWhenDone) && $flow->continueWhenDone){
				$parentFlow = $this->flowMap[$flow->parentIndex];
				if(isset($parentFlow->infoIndex)){
					$this->removeBranches($this->branchInfoMap[$parentFlow->infoIndex]);
					$this->branchInfoMap->remove($parentFlow->infoIndex);
					unset($parentFlow->infoIndex);
				}
				$parentFlow->active = true;
				$this->run($flow->parentIndex, null);
			}
		}
		else if($result instanceof FlowCancel){
			$root_index = $this->getRootFlowIndex($flowIndex);
			$root_flow = $this->flowMap[$root_index];
			if(!isset($root_flow->infoIndex))
				throw new FlowyException('');
			$this->removeBranches($this->branchInfoMap[$root_flow->infoIndex]);
			$this->start($root_flow, $root_index);
		}
		else{
			throw new FlowyException('');
		}
	}
}

}// class_exists