<?php
namespace Flowy;

if(!class_exists('Flowy\Awaitable')){

abstract class Awaitable{
	/** @var (callable, bool)[] */
	protected $branches = [];

	/** @var callable */
	protected $inactiveHandler = null;

	public function branch(callable $flowDef, bool $continueWhenDone = false){
		if(!(new \ReflectionFunction($flowDef))->isGenerator())
			throw new FlowyException('$flowDef is not an anonymouse function that returns Generator.');
		$this->branches[] = [ $flowDef, $continueWhenDone ];
		return $this;
	}

	public function inactive(callable $handler){
		$this->inactiveHandler = $handler;
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