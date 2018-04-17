<?php
namespace Flowy;

use pocketmine\event\Event;

if(!class_exists('Flowy\ListenAwaitable')){

class ListenAwaitable{
	/** @var string[] */
	protected $targets = [];

	/** @var callable[] */
	protected $filters = [];

	/** @var (callable, bool)[] */
	protected $branches = [];

	/** @var callable */
	protected $inactiveHandler = null;

	public function filter(callable $filter){
		$this->filters[] = $filter;
		return $this;
	}

	public function branch(callable $flowDef, bool $continueWhenDone = false){
		if(!(new \ReflectionFunction($flowDef))->isGenerator())
			throw new FlowyException('');
		$this->branches[] = [ $flowDef, $continueWhenDone ];
		return $this;
	}

	public function inactive(callable $handler){
		$this->inactiveHandler = $handler;
	}

	public function addListenTarget(string $event){
		if(!is_subclass_of($event, Event::class))
			throw new FlowyException("{$event}はEventではありません");
		if(!in_array($event, $this->targets))
			$this->target[] = $event;
	}

	public function getTargetEvents(){
		return $this->targets;
	}

	public function getFilters(){
		return $this->filters;
	}

	public function hasBranches(){
		return count($this->branches) > 0;
	}

	public function getBranches(){
		return $this->branches;
	}

	public function handleInactive(){
		if($this->inactiveHandler !== null){
			($this->inactiveHandler)();
		}
	}
}

}// class_exists