<?php
namespace micro\orm;

/**
 * Utilitaires de mappage Objet/relationnel
 * @author jc
 * @version 1.0.0.3
 * @package orm
 */
class OrmUtils{
	public static function isSerializable($class,$member){
		if (Reflexion::getAnnotationMember($class,$member,"@transient")!==false || Reflexion::getAnnotationMember($class,$member,"@manyToOne")!==false ||
				Reflexion::getAnnotationMember($class,$member,"@manyToMany")!==false || Reflexion::getAnnotationMember($class,$member,"@oneToMany")!==false)
			return false;
		else
			return true;
	}

	public static function isNullable($class,$member){
		$ret=Reflexion::getAnnotationMember($class,$member,"@column");
		if (!$ret)
			return false;
		else
			return $ret->nullable;
	}

	public static function getFieldName($class,$member){
		$ret=Reflexion::getAnnotationMember($class, $member, "@column");
		if($ret===false)
			$ret=$member;
		else
			$ret=$ret->name;
		return $ret;
	}

	public static function getTableName($class){
		$ret=Reflexion::getAnnotationClass($class, "@table");
		if(\sizeof($ret)==0)
			$ret=$class;
		else{
			$ret=$ret[0]->name;
		}
		return $ret;
	}

	public static function getKeyFieldsAndValues($instance){
		$kf=Reflexion::getMembersWithAnnotation(get_class($instance), "@id");
		return Reflexion::getPropertiesAndValues($instance,$kf);
	}

	public static function getFirstKey($class){
		$kf=Reflexion::getMembersWithAnnotation($class, "@id");
		if(sizeof($kf)>0)
			return $kf[0]->getName();
	}

	public static function getFirstKeyValue($instance){
		$fkv=OrmUtils::getKeyFieldsAndValues($instance);
		return reset($fkv);
	}

	/**
	 * @param object $instance
	 * @return mixed[]
	 */
	public static function getManyToOneMembersAndValues($instance){
		$ret=array();
		$class=get_class($instance);
		$members=Reflexion::getMembersWithAnnotation($class, "@manyToOne");
		foreach ($members as $member){
			$annot=OrmUtils::getJoinColumn($class, $member->getName());
			$memberAccessor="get".ucfirst($member->getName());
			if(method_exists($instance,$memberAccessor)){
				$memberInstance=$instance->$memberAccessor();
				if(isset($memberInstance)){
					$keyValues=OrmUtils::getKeyFieldsAndValues($memberInstance);
					if(sizeof($keyValues)>0)
						$ret[$annot->name]=reset($keyValues);
				}
			}
		}
		return $ret;
	}

	/**
	 * @param object $instance
	 * @param string $memberKey
	 * @param array $array
	 * @return boolean
	 */
	public static function exists($instance,$memberKey,$array){
		$accessor="get".ucfirst($memberKey);
		if(method_exists($instance, $accessor)){
			if($array!==null){
				foreach ($array as $value){
					if($value->$accessor()==$instance->$accessor())
						return true;
				}
			}
		}
		return false;
	}

	public static function getJoinColumn($class,$member){
		$annot=Reflexion::getAnnotationMember($class, $member, "@joinColumn");
		if($annot!==false){
			$annot=new \JoinColumn();
			$annot->name="id".ucfirst(OrmUtils::getTableName(ucfirst($member)));
		}
		return $annot;
	}

	public static function isMemberInManyToOne($class,$array,$member){
		foreach ($array as $memberMTO){
			$annot=Reflexion::getAnnotationMember($class, $memberMTO->getName(), "@joinColumn");
			if($annot!==false && $annot->name==$member)
				return true;
		}
		return false;
	}
}
