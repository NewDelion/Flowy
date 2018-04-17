<?php
namespace Flowy;

if(!class_exists('Flowy\BranchInfo')){

class BranchInfo{
	protected $mainFlowIndex = -1;
	protected $branchFlowIndexes = [];

	public function setMainFlowIndex(int $flowIndex){
		$this->mainFlowIndex = $flowIndex;
	}

	public function getMainFlowIndex(){
		return $this->mainFlowIndex;
	}

	public function addBranchFlowIndex(int $flowIndex){
		if(!in_array($flowIndex, $this->branchFlowIndexes)){
			$this->branchFlowIndexes[] = $flowIndex;
		}
	}

	public function getBranchFlowIndexes(){
		return $this->branchFlowIndexes;
	}
}

}// class_exists