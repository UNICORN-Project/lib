<?php 

class PsychoFrame extends GenericORMapper
{
	public static function psychoField($argDBO, $argModelName, $argExtractionCondition=NULL, $argBinds=NULL, $argAutoReadable=TRUE, $argSeqQuery=NULL){
		return self::getModel($argDBO, $argModelName, $argExtractionCondition, $argBinds, $argAutoReadable, $argSeqQuery);
	}
}

?>
