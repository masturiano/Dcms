<?php

/**
 * @category USAPTool_Modules
 * @package Dcms_Service
 * @copyright 2012
 * @author bteves
 * @version $Id$
 */
class Dcms_Service_Base extends USAP_Service_ServiceAbstract {

    /**
     *
     * @var Zend_Config 
     */
    protected $config;

    public function __construct(Zend_Config $config) {
        $this->config = $config;
    }

    public function baseEncoding($value) {

        return base64_encode(htmlentities($this->replaceQuote($value)));
    }

    /**
     *
     * @param <type> $text
     * @return <type>
     */
    public function replaceQuote($text) {
//      $text = $this->normalize($text);
        $text = $this->removeSmartQuotes($text);
        $text = $this->clearAscii($text);
        $text = $this->clearUTF($text);
        return $text;
    }

    /**
     * 
     * @param type $s
     * @return string 
     */
    public function clearAscii($s) {
        setlocale(LC_ALL, 'en_US.UTF8');

        $r = '';
        $s1 = iconv('Windows-1252', 'ASCII//TRANSLIT', $s);
        for ($i = 0; $i < strlen($s1); $i++) {
            $ch1 = $s1[$i];
            $ch2 = mb_substr($s, $i, 1);

            $r .= $ch1 == '?' ? $ch2 : $ch1;
        }
        return $r;
    }

    /**
     *
     * @param <type> $text
     * @return <type>
     */
    public function removeSmartQuotes($text) {
        // First, replace UTF-8 characters.
        $text = str_replace(
                array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"), array("'", "'", '"', '"', '-', '--', '...'), $text);
        // Next, replace their Windows-1252 equivalents.
        $text = str_replace(
                array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)), array("'", "'", '"', '"', '-', '--', '...'), $text);
        return $text;
    }

    /**
     *
     * @param <type> $s
     * @return <type>
     */
    public function clearUTF($s) {
        setlocale(LC_ALL, 'en_US.UTF8');
        $r = '';
        $s1 = @iconv('UTF-8', 'ASCII//TRANSLIT', $s);
        $j = 0;
        for ($i = 0; $i < strlen($s1); $i++) {
            $ch1 = $s1[$i];
            $ch2 = @mb_substr($s, $j++, 1, 'UTF-8');
            if (strstr('`^~\'"', $ch1) !== false) {
                if ($ch1 <> $ch2) {
                    --$j;
                    continue;
                }
            }
            $r .= ( $ch1 == '?') ? $ch2 : $ch1;
        }
        return $r;
    }

    public function getRegistryAclUser() {
        $getRegistry = array();
        $getRegistry['acl'] = Zend_Registry::get('acl');
        $getRegistry['user'] = Zend_Registry::get('user');
        return $getRegistry;
    }

    public function mergeUniqueArray($select1, $select2) {

        if (isset($select1) && isset($select2)) {
            $finalResult = array_unique(array_merge($select1, $select2));
        } elseif (isset($select1) && !isset($select2)) {
            $finalResult = $select1;
        } elseif (!isset($select1) && isset($select2)) {
            $finalResult = $select2;
        }
        return $finalResult;
    }

    public function hydraConnect($data = null, $serviceIdentifier = 'coupon', $method = 'read', $env = "working", $sort = "", $decode = false) {
        $config = $this->config->service->sourceobject->toArray();

        try {
            $serviceObject = $config['coupon'];
            $serviceObject['method'] = $method;
			$serviceObject['data'] =
                    array(
                        'query' => $data,
                        'type' 	=> $serviceIdentifier,
                        'env' 	=> $env,
				);
			if(isset($data['data'])){
				$serviceObject['data'] = array_merge($serviceObject['data'], $data['data']);	
                                unset($serviceObject['data']['query']['data']);
			}
            $sort != "" ? $serviceObject['data']['sort'] = $sort : "";
            ($decode) ? $serviceObject['data']['decode'] = $decode : "";
            $getResults = Hydra_Helper::loadClass(
                            $serviceObject['url'], 
							$serviceObject['version'], 
							$serviceObject['service'], 
							$method, 
							$serviceObject['data'], 
							$serviceObject['httpmethod'], 
							$serviceObject['id'], 
							$serviceObject['format']
            );
            if (is_string($getResults) || !isset($getResults['_payload']['result'][$serviceObject['method']])) {
                return array();
            }
            
            return $getResults['_payload']['result'][$serviceObject['method']];
        } catch (Exception $e) {
            return array();
        }
    }

    public function getRecords($serviceResult) {
        if (count($serviceResult) == 0 && !isset($serviceResult['result']['records']))
            return array();
        return $serviceResult['result']['records']; 
    } 
	
	public function getRecordCount($serviceResult) {
        if (count($serviceResult) == 0 && !isset($serviceResult['result']['records']))
            return 0;
        return (int) $serviceResult['result']['record_count']; 
    }

    public function getSites($getSiteId = false) {
		try{
			$result = $this->hydraConnect(array(), "domains", "read", "live", array('name' => 1));
			$sitesArray = array();
			$sites = $this->getRecords($result);
			if(count($sites) > 0){
				foreach ($sites as $key => $value) {
					if(!empty($value['name'])){
						$indexValue = (!$getSiteId) ? $value['name'] : $value['siteId'];
						$sitesArray[$indexValue] = $value['name'];
					}
				}
			}
			
			return $sitesArray;
		}catch(Exception $e){
			return array();
		}
        
    }

    public function getRestrictions($restrtiction = "", $limitskip = array()) {
		try{
			if($restrtiction == ""){
                                if(isset($limitskip)) {
                                    $query['data'] = array_merge($limitskip, array('sort' => array('templateName' => "1")));
                                    $result = $this->hydraConnect(array_merge($this->queryIfDeleted(array()), $query), "template", "read");
                                } else {
                                    $result = $this->hydraConnect($this->queryIfDeleted(array()), "template", "read");
                                }

				$template = $this->getRecords($result);
                                
				if (count($template) == 0)
					Throw new Exception("No restrictions found");
				$templateArray = array();
				foreach ($template as $key => $value) {
					if(!empty($value['templateId']) && !empty($value['templateName'])) {
						$templateArray[$value['templateId']] = $value['templateName'];
					} 
				}
				return $templateArray;
			}else{
				$result = $this->hydraConnect(array("templateId" => (int) $restrtiction), "template", "read");
				$template = $this->getRecords($result);
				return $template[0];
			}
			
		}catch(Exception $e){
			return array();
		}
    }

    public function getSiteInfo($query) {
		if(!is_array($query)){
			$query = array('name' => $query);
		}
        $getSiteInfo = $this->hydraConnect($query, "domains", "read", "live");
        $getSiteInfo = $this->getRecords($getSiteInfo);
        return isset($getSiteInfo[0]) ? $getSiteInfo[0] : array();
    }

    public function queryIfDeleted($query) {
        return array_merge(
                        $query, array(
                    '$or' => array(
                        array(
                            'deleted' => array(
                                '$exists' => false
                            )
                        ),
                        array(
                            'deleted' => false
                        )
                    )
                        )
        );
    }

    public function convertDateTimezone($date, $timezone) {
        $timezone = new DateTimeZone($timezone);
        $dateInTimezone = new DateTime($date);
        $dateInTimezone->setTimeZone($timezone);
        return $dateInTimezone;
    }

    public function arrayRecursiveEncode(&$haystack) {

        if (!is_array($haystack)) {

            $isString = $this->isString($haystack);
            return ($isString) ? $this->baseEncoding($haystack) : $haystack;
        }

        foreach ($haystack as $key => $value) {

            $isStringKey = $this->isString($key);
            if (is_array($value)) {
                if(empty($value)) {
                    ($isStringKey) ? $return[$this->arrayRecursiveEncode($key)] = $value : $return[$key] = $value;
                } else {
                    ($isStringKey) ? $return[$this->arrayRecursiveEncode($key)] = $this->arrayRecursiveEncode($value) : $return[$key] = $this->arrayRecursiveEncode($value);
                }
            } else {
                $isString = $this->isString($value);
                $newValue = ($isString) ? $this->baseEncoding($value) : $value;
                ($isStringKey) ? $return[$this->baseEncoding($key)] = $newValue : $return[$key] = $newValue;
            }
        }
        return $return;
    }

    public function isString($value) {
        if (is_string($value)) {
            if (is_object($value)) {
                return false;
            } else {
                if ($value == 'sec' || $value == 'usec') {
                    return false;
                }
                return true;
            }
        } else {
            return false;
        }
    }

    public function getOwner($owner = "") {
		try{
			if($owner == ""){
				$result = $this->hydraConnect(array(), "owners", "read", "live");
				$owners = $this->getRecords($result);
				$ownersArray = array();
				if(count($owners) > 0){
					foreach ($owners as $key => $value) {
						if(!empty($value['id']) && !empty($value['owner'])){
							$ownersArray[$value['id']] = $value['owner'];
						}
					}
				}
				return $ownersArray;
			
			}else{
				$result = $this->hydraConnect(array("_id" => $owner), "owners", "read", "live");
				$owner = $this->getRecords($result);
				return isset($owner[0]) ? $owner[0] : array();
			}
			
		}catch(Exception $e){
			return array();
		}
        
    }

    public function getPaginator($hydraService, $request) {
        $paginatorAdapter = new USAP_Paginator_Adapter_HydraApi($hydraService);
        $paginator = new Zend_Paginator($paginatorAdapter);
        $paginator->setCurrentPageNumber($request['page']);
        $paginator->setDefaultItemCountPerPage($request['countperpage']);
        return $paginator;
    }
    
    public function arrayRecursiveDiff($aArray1, $aArray2) {
        $aReturn = array();

        foreach ($aArray1 as $mKey => $mValue) {
            if(is_array($aArray2)){
                if (array_key_exists($mKey, $aArray2)) {
                    if (is_array($mValue)) {
                        $aRecursiveDiff = $this->arrayRecursiveDiff($mValue, $aArray2[$mKey]);
                        if (count($aRecursiveDiff)) {
                            $aReturn[$mKey] = $aRecursiveDiff;
                        }
                    } else {
                        if ($mValue != $aArray2[$mKey]) {
                            $aReturn[$mKey] = $mValue;
                        }
                    }
                } else {
                    $aReturn[$mKey] = $mValue;
                }
            }else{
                $aReturn[$mKey] = $mValue;
            }
            
        }
        return $aReturn;
    }
	
    public function setTimeframeMessage($expiration){
        $timeFrame = "";
		if ($expiration['type'] == 'expiring' || $expiration['type'] == 'firstxusers') {
				if(isset($expiration['startDate']['sec']) && isset($expiration['endDate']['sec']) 
						&& is_int($expiration['startDate']['sec']) && is_int($expiration['endDate']['sec'])
				){
						$showTime = ($expiration['type'] == 'expiring') ? "h:i:s a" : "";
						$startDateSec = $expiration['startDate']['sec'];
						$endDateSec = $expiration['endDate']['sec'];
						$timeFrame = "<b>" . date("M d, Y $showTime",$startDateSec) . "</b> to <b>" . date("M d, Y $showTime",$endDateSec) . "</b>";
				}
		} else if ($expiration['type'] == 'recurring') {
				if(isset($expiration['startDayOfMonth']) 
				&& isset($expiration['endDayOfMonth'])
				&& isset($expiration['numberOfMonths'])
				&& isset($expiration['monthYearStart'])
				){
						$monthYearStart = explode(", ", $expiration['monthYearStart']);
						$monthYearStartTime = strtotime("{$monthYearStart[0]}/1/{$monthYearStart[1]}");
						$monthStart = date("M", $monthYearStartTime);
						$yearStart = date("Y", $monthYearStartTime);
						$numberOfMonths = ($expiration['numberOfMonths'] > 1) ? $expiration['numberOfMonths'] . " months" : $expiration['numberOfMonths'] . " month"; 
						$timeFrame = "Every <b>{$expiration['startDayOfMonth']}</b> to <b>{$expiration['endDayOfMonth']}</b> of the month, for <b>{$numberOfMonths}</b>  starting <b>{$monthStart}</b>, <b>{$yearStart}</b>";
				}
		} else {
			$timeFrame = "none";
		}
			return $timeFrame;
    }

    public function getUniqueKey() {

        $query = array();
        $result = $this->hydraConnect($query, 'keys', 'getUniqueKey', 'live');
        if(isset($result['ok']) && $result['ok'] == 1) {
            if(is_array($result['value']) && count($result['value']) > 0) {
                return (int) $result['value']['key_counter'] + 1;
            } else {
                return (int) 1;
            }
        } else {
            return "An error occured while requesting on the API for an unique key";
        }
    }
    
    public function lettersOnly($value) {
        return preg_replace("[^A-Za-z]", "", $value);
    }
    
    public function numbersOnly($value) {
        return preg_replace("[^0-9]", "", $value);
    }
    
    public function letNumOnly($value) { 
        return preg_replace("[^A-Za-z0-9]", "", $value);

    }
	
	public function domainCharsOnly($value){
		return preg_replace("[^A-Za-z0-9.]", "", $value);
		//return ereg_replace("^[a-zA-Z0-9-.]+(.com|.in|.co|.info|.name|.net|.org)$", "", $value);
	}
    
	
	public function search($query, $serviceType, $useQuery = false) {
        $query = ($useQuery) ? $query : $this->queryIfDeleted($query);
        $result = $this->hydraConnect($query, $serviceType, 'read');
        return isset($result['result']['records']) ? $result['result']['records'] : array();
    }
    
    public function getHydraPaginator($limit, $skip, $identifier, $sort = array(), $searchQuery = array(), $method = "read", $env = "working"){
        try{
                $paginator = array();

                $query['data'] = array(
                        'limitskip' => array(
                            'limit' => $limit,
                            'skip'  => $skip
                        )
                    );
                (!empty($sort)) ? $query['data']['sort'] = $sort : "";
                
                $result = $this->hydraConnect(array_merge($this->queryIfDeleted($searchQuery), $query), $identifier, $method, $env);
                $total = (int)$this->getRecordCount($result);
                
                if ( $total == 0){
                        return array('empty' => true);
                }
                
                $records = $this->getRecords($result);
                $paginator['items'] = $records;
                $paginator['count'] = $total;
                $paginator['limit'] = $limit;
                $paginator['skip'] = $skip;
                

                return $paginator;
        }catch(Exception $e){
                return array();
        }
    }
	

}
?>

