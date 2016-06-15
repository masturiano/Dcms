<?php
//obsidian
class Dcms_Model_Coupon {


	private $_expirationType;
	private $_action;
	private $_username;
	
	private $_couponDetails = array();
	
	private $_expirationDetails = array();
	
	private $_baseService;
	
	private $_couponModel = array(
									'code',
									//'name',
									'type',
									//'appeasement',
									'couponAppliesTo',
									'versionId',
									//'class',
									//'owner',
									'domains',
									'siteId',
									'channelCode',
									'discountVal',
									'amountQualifier',
									'ceilingAmount',
								);
		
	private $_nonExpiringModel = array(
									'type',
									'status'
								);	
								
	private $_expiringModel = array(
									'type',
									'startDate',
									'endDate',
									'timezone',
								);			
								
	private $_recurringModel = array(
									'type',
									'startDayOfMonth',
									'endDayOfMonth',
									'numberOfMonths',
									'monthYearStart',
									'startDate',
									'endDate',
									'timezone');	
								
								
	private $_firstxusersModel = array(
									'type',
									'initialQuantity',
									'remainingQuantity',
									'startDate',
									'endDate',
									'timezone',
									'status'
								);
	

	private $_documentModel = array(
									'coupon',	
									'expiration',	
								);
	
	private $_documentDetails;
	private $_timezone;
		
	private $_errorContainer;
	private $_couponFormModel;
        
        private $_formValues;
			
	public function __construct($formValues, $action){
		$this->_baseService = USAP_Service_ServiceAbstract::getService("Dcms_Service_Base", "");
		$this->_couponDetails['versionId'] = time();
		$this->_couponFormModel = new Dcms_Model_CouponValues();
		$this->_errorContainer = array();
                $this->_formValues = $formValues;
                $this->_action = $action;
                $this->_timezone = $formValues['timezone'];
                
                        if ($action == "update" && isset($this->_formValues['code_checker'])) {
                            $this->setCouponName($this->_formValues['code'], $this->_formValues['code_checker']);
                        } else {
                            $this->setCouponName($this->_formValues['code']);
                        }

                        $this->setDiscountVal($this->_formValues['discountVal']);
                        $this->setAmountQualifier($this->_formValues['amountQualifier']);
                        $this->setCeilingAmount($this->_formValues['ceilingAmount']);
                        $this->setType($this->_formValues['type']);
                        $this->setAppeasement($this->_formValues['appeasement']);
                        $this->setCouponAppliesTo($this->_formValues['couponAppliesTo']);
                        $this->setStatus($this->_formValues['publish']);
                        $this->setClass($this->_formValues['class']);
                        $this->setOwner($this->_formValues['owner']);
                        $this->setSiteInfo($this->_formValues['site']);


                        $expirationType = "{$this->_formValues['expiration']}";
                        if (method_exists($this, "expiring")) { 
                            $this->$expirationType();
                        } else {
                            //return array with error
                            return array(
                                'error' => array(
                                    'INVALID' => "Expiration type: {$expirationType} does not exists."
                                )
                            );
                        }

                }

                                              
	public function setUsername($username){
		$this->_username = $username;
	}
	
	
	public function setCouponName($code, $codeChecker = ""){
		if($this->_action != ""){
			$code = trim(strtoupper($this->_baseService->letNumOnly($code)));
			if($this->_action == "create"){
				$this->_checkCouponExistence($code);
			}else if($this->_action == "update"){
				if($codeChecker != $code){
					$this->_checkCouponExistence($code);
				}else{
					$this->_couponDetails['code'] = $code;
					$this->_couponDetails['name'] = $code;
				}
			}
			
		}
	}
	
	private function _checkCouponExistence($code){
		$searchCouponIfExists = $this->_searchCoupon($code, "coupon", true);
		if (count($searchCouponIfExists) > 0) {
			$this->_errorContainer['INVALID'][] = "Coupon name already exists";
		}else{
			$this->_couponDetails['code'] = $code;
			$this->_couponDetails['name'] = $code;
		}
	}
	
	public function setDiscountVal($discountVal = array()){
		$this->_setValidateAmounts('discountVal', $discountVal);
	}
	
	public function setAmountQualifier($amountQualifier = array()){
		$this->_setValidateAmounts('amountQualifier', $amountQualifier);
	}
	
	private function _setValidateAmounts($index, $value){
		if (count($value) > 0){
			$valueToInt = array_map(array($this, 'valueToInt'), $value);
			$this->_couponDetails[$index] = $valueToInt;
		}else{
			$this->_errorContainer['EMPTY'][] = $index;
		}
	}
	
	public function setCeilingAmount($ceilingAmount){
        $this->_couponDetails['ceilingAmount'] = $this->valueToInt($ceilingAmount);
	}
	  
	public function setType($type){
		$typesArray = $this->_couponFormModel->getTypes();
		if(!isset($typesArray[$type])){
			$this->_errorContainer['INVALID'][] = 'type';
		}
		$this->_couponDetails['type'] = $type;
	}
	
	public function setAppeasement($appeasement){
		if($appeasement == true || $appeasement == false){
			$this->_couponDetails['appeasement'] = $appeasement ? true : false;
		}else{
			$this->_errorContainer['INVALID'][] = 'appeasement';
		}
	}
	
	public function setCouponAppliesTo($couponAppliesTo){
		$couponAppliesToArray = $this->_couponFormModel->getApplyDiscountTo();
		if(!isset($couponAppliesToArray[$couponAppliesTo]) && $couponAppliesTo != "FREESHIPPING"){
			$this->_errorContainer['INVALID'][] = 'couponAppliesTo';
		}else{
			$this->_couponDetails['couponAppliesTo'] = $couponAppliesTo;
		}
	}
	
	public function setStatus($status){
		$this->_documentDetails['status'] = $status ? "published" : "testing";
	}
	
	public function setClass($class){
		$classArray = $this->_couponFormModel->getApplyCouponTo();
		if(!isset($classArray[$class])){
			$this->_errorContainer['INVALID'][] = 'class';
		}else{
			$this->_couponDetails['class'] = $this->valueToInt($class);
		}
		
	}
	
	public function setDomains($domains){
		$this->_validateIfEmpty('domains', $domains);
		$this->_couponDetails['domains'] = array($this->_baseService->domainCharsOnly($domains));
	}
	
	public function setOwner($owner){
		$getOwner = $this->_baseService->getOwner($owner);
			if(count($getOwner) > 0){
				$this->_couponDetails['owner'] = $owner;
			}else{
				$this->_errorContainer['INVALID'][] = 'Owner selected no longer exists.';
			}
	}
	
	public function setSiteId($siteId){
		$this->_validateIfEmpty('siteId', $siteId);
		$this->_couponDetails['siteId'] = array($siteId);
	}
	
	public function setChannelCode($channelCode){
		$this->_couponDetails['channelCode'] = $channelCode;
	}
	
	public function setSiteInfo($site){
		$siteInfo = $this->_baseService->getSiteInfo($site);
		if(count($siteInfo) > 0){
			$this->setDomains($site);
			$this->setSiteId($siteInfo['siteId']);
			$this->setChannelCode($siteInfo['channelCode']);
		}
		
	}
	
	private function _validateIfEmpty($index, $value){
		$value = trim($value);
		if($value == "" || $value === null){
			$this->_errorContainer['EMPTY'][] = $index;
		}
	}

	public function setRestrictions($values){
		if($values['applyrestriction_checkbox'] && $values['restrictions'] != ""){
			$templateInfo = $this->_baseService->getRestrictions($values['restrictions']);
			if(count($templateInfo) > 0){
				$this->_documentDetails['templateId'] = (int) $values['restrictions'];
				$this->_documentDetails['restrictions'] = $templateInfo['templateName'];
			}else{
				$this->_documentDetails['templateId'] = "";
				$this->_documentDetails['restrictions'] = "";
			}
		}else{
			$this->_documentDetails['templateId'] = "";
			$this->_documentDetails['restrictions'] = "";
		}
		
	}

	public function getTimezone(){
		$this->_expirationDetails['timezone'] = $this->_timezone;
	}
	
	public function nonexpiring(){
		$this->_expirationDetails['type'] = __FUNCTION__;
		if (isset($this->_formValues['applyrestriction_checkbox'])) {
			$this->setRestrictions($this->_formValues);
		} 
	}
	
	/*
	validate start date
		validate format
		valid date
		valid minutes
	validate end date
		validate format
		valid date
		valid minutes
	validate both dates 
		end date should not be ahead of start date
		
	*/
	public function expiring(){
		try{
                        $values = $this->_formValues;
			if($this->_isset($values, 'from') && $this->_isset($values, 'to')
			&& $this->_isset($values, 'from_min') && $this->_isset($values, 'to_min')
			){
				$from = $values['from'];
				$to = $values['to'];
				$from_min = $values['from_min'];
				$to_min = $values['to_min'];
				
				if(!$this->_isDateValid($from) || !$this->_isDateValid($to)){
					$this->_errorContainer['INVALID'][] = "Invalid dates given";
				}else if(!$this->_isTimeValid($from_min) || !$this->_isTimeValid($to_min)){
					$this->_errorContainer['INVALID'][] = "Invalid time given";
				}else if(($this->_isPastDate($from) || $this->_isPastDate($to)) && $this->_action == "create"){ 
					$this->_errorContainer['INVALID'][] = "Date should not be from the past";
				}else if(!$this->_datesValid($from, $to, $from_min, $to_min)){
					$this->_errorContainer['INVALID'][] = "Invalid dates given";
				}else{
					$startDate = $from . " " . $from_min;
					$endDate = $to . " " . $to_min;
					$startDate = $this->_baseService->convertDateTimezone($startDate, $this->_timezone);
					$endDate = $this->_baseService->convertDateTimezone($endDate, $this->_timezone);
					
					$this->_expirationType = __FUNCTION__;
					$this->_expirationDetails['type'] = $this->_expirationType;
					$this->_expirationDetails['startDate'] = $startDate->getTimestamp();
					$this->_expirationDetails['endDate'] = $endDate->getTimestamp();
					$this->getTimezone();
					if (isset($values['applyrestriction_checkbox'])) {
						$this->setRestrictions($values);
					}
				}
			}else{
				$this->_errorContainer['INVALID'][] = "Inputs for " . __FUNCTION__ . " are required";
			}
			
		}catch(Exception $e){
			$this->_errorContainer['INVALID'][] = "Inputs for " . __FUNCTION__ . " are required";
		}
	}
	
	
	/*
	validate start day of month
		is number
		0 is not valid
	validate end day of month
		is number
		0 is not valid
	validate start and end day of month
		end day should not be ahead of start day
		start day and end day should be VALID day of the month ie. leap year for february
	validate number of months
		should not be more than 12 months
		should be number
	validate 
	validate start date
		validate format
		valid date
		valid minutes
	validate end date
		validate format
		valid date
		valid minutes
	validate both dates 
		end date should not be ahead of start date
	*/
	public function recurring(){
		try{
                        $values = $this->_formValues;
			if($this->_isset($values, 'startday')
				&& $this->_isset($values, 'endday')
				&& $this->_isset($values, 'recurring_period')
				&& $this->_isset($values, 'recurring_period_month')
				&& $this->_isset($values, 'recurring_period_year')
			){
				$startDayOfMonth = $this->valueToInt($values['startday']);
				$endDayOfMonth = $this->valueToInt($values['endday']);
				$numberOfMonths = $this->valueToInt($values['recurring_period']);
				$recurring_period_month = $this->_baseService->numbersOnly($values['recurring_period_month']);
				$recurring_period_year = $this->_baseService->numbersOnly($values['recurring_period_year']);
				
				$startDate_validation = $recurring_period_month . "/" . $startDayOfMonth . "/" . $recurring_period_year;
				$endDate_validation = $recurring_period_month . "/01/" . $recurring_period_year;
				$startDate = $this->_baseService->convertDateTimezone($startDate_validation, $this->_timezone);
				
				
				if($startDayOfMonth == 0 || $endDayOfMonth == 0 || $numberOfMonths == 0){
					$this->_errorContainer['INVALID'][] = "Dates should not be zero (0).";
				}else if($startDayOfMonth > $endDayOfMonth){
					$this->_errorContainer['INVALID'][] = "Start Day should not be ahead of End day";
				}else if(!$this->_isMonthValid($recurring_period_month) || !$this->_isYearValid($recurring_period_year) || !$this->_numberOfMonthsValid($numberOfMonths)){
					$this->_errorContainer['INVALID'][] = "Invalid month, year, or number of months";
				}else if(!$this->_isDateValid($startDate_validation) || !$this->_isDateValid($endDate_validation)){
					$this->_errorContainer['INVALID'][] = "Invalid date/s";
				}else{	
					$startDate_time = $startDate->getTimestamp();
                                        if($numberOfMonths == 1){
                                            $endMonth = $this->valueToInt($recurring_period_month);
                                            $endYear = $this->valueToInt($recurring_period_year);
                                            $addNumberOfMonthsToEndDate = strtotime($startDate_validation);
                                        }else{
                                            $addNumberOfMonthsToEndDate = strtotime(date("Y-m-d", strtotime($endDate_validation)) . " +{$numberOfMonths} months");
                                            $endMonth = date("m", $addNumberOfMonthsToEndDate);
                                            $endYear = date("Y", $addNumberOfMonthsToEndDate);
                                        }
                                        
                                        $buildEndDate = $endMonth . "/" . $endDayOfMonth . "/" . $endYear;
                                        $validNumberOfDaysInMonth = date("t", $addNumberOfMonthsToEndDate);
                                        
                                        if(!$this->_isDateValid($buildEndDate) && $endDayOfMonth > $validNumberOfDaysInMonth){
                                            $buildEndDate = $endMonth . "/" . $validNumberOfDaysInMonth . "/" . $endYear;
                                        }
                                        $endDate = $this->_baseService->convertDateTimezone($buildEndDate, $this->_timezone);
                                        $endDate_time = $endDate->getTimestamp();
                                        
                                        //convert start day of the month due to changing end  end month 
                                        $startDayOfMonthConverted = $this->valueToInt($startDate->format("j"));
                                        $getEndDayOfMonthUsingStartMonth = $recurring_period_month . "/" . $endDayOfMonth . "/" . $recurring_period_year;
                                        //convert end day of the month due to changing end  end month 
                                        $endDayOfMonthConversion = $this->_baseService->convertDateTimezone($getEndDayOfMonthUsingStartMonth, $this->_timezone);
                                        $endDayOfMonthConverted = $this->valueToInt($endDayOfMonthConversion->format("j"));
                                        $this->_expirationType = __FUNCTION__;
					$this->_expirationDetails['type'] 				= $this->_expirationType;
					$this->_expirationDetails['startDayOfMonth']        = $startDayOfMonth;
					$this->_expirationDetails['startDayOfMonth_converted']   	= $startDayOfMonthConverted;
					$this->_expirationDetails['endDayOfMonth']     	= $endDayOfMonth;
					$this->_expirationDetails['endDayOfMonth_converted']     	= $endDayOfMonthConverted;
					$this->_expirationDetails['numberOfMonths']    	= $numberOfMonths;
					$this->_expirationDetails['monthYearStart']    	= $recurring_period_month . ", " . $recurring_period_year;
					$this->_expirationDetails['startDate'] 			= $startDate_time;
					$this->_expirationDetails['endDate'] 			= $endDate_time;
					$this->getTimezone();
//					echo "<pre>", print_r($this->_expirationDetails);exit;
                                        /*echo "<br>";
                                        echo $startDate->format("m/d/Y") . "<br>";
                                        echo $endDate->format("m/d/Y") . "<br>";
                                        exit;*/
					if (isset($values['applyrestriction_checkbox'])) {
						$this->setRestrictions($values);
					}		
				}
					
			}else{
				$this->_errorContainer['INVALID'][] = "Inputs for " . __FUNCTION__ . " are required";
			}
			
		}catch(Exception $e){
			$this->_errorContainer['INVALID'][] = "Inputs for " . __FUNCTION__ . " are required";
		}
	}
	
	/*
	validate start date
		validate format
		valid date
	validate end date
		validate format
		valid date
	validate both dates 
		end date should not be ahead of start date
		
	
	*/
	public function firstxusers(){
		try{
                        $values = $this->_formValues;
			if($this->_isset($values, 'from') && $this->_isset($values, 'to')
				&& $this->_isset($values, 'valid_first_x_users')
			){
				$this->_expirationType = __FUNCTION__;
				$this->_expirationDetails['type'] = $this->_expirationType;
				$startDate = $values['from'];
				$endDate = $values['to'];
				if(!$this->_isDateValid($startDate) || !$this->_isDateValid($endDate)){
					$this->_errorContainer['INVALID'][] = "Invalid dates given";
				}else if(($this->_isPastDate($startDate) || $this->_isPastDate($endDate)) && $this->_action == "create"){
					$this->_errorContainer['INVALID'][] = "Date should not be from the past";
				}else if(!$this->_datesValid($startDate, $endDate)){
					$this->_errorContainer['INVALID'][] = "Invalid dates given";
				}else{
					$startDate = $this->_baseService->convertDateTimezone($startDate, $this->_timezone);
					$endDate = $this->_baseService->convertDateTimezone($endDate, $this->_timezone);
					
					$this->_expirationDetails['startDate'] = $startDate->getTimestamp();
					$this->_expirationDetails['endDate'] = $endDate->getTimestamp();
					$this->_expirationDetails['initialQuantity'] 	= $this->valueToInt($values['valid_first_x_users']);
					$this->_expirationDetails['remainingQuantity'] = $this->valueToInt($values['valid_first_x_users']);
					
					$this->getTimezone();
					if (isset($values['applyrestriction_checkbox'])) {
						$this->setRestrictions($values);
					}
				}
			}else{
				$this->_errorContainer['INVALID'][] = "Inputs for " . __FUNCTION__ . " are required";
			}
		}catch(Exception $e){
			$this->_errorContainer['INVALID'][] = "Inputs for " . __FUNCTION__ . " are required";
		}
	}
	
	
	//array_map(array($this, 'add_val'), array_chunk($drop_val, count($optionsArray['heading_x'])));
	
	private function _isset($array =  array(), $key){
		return !empty($array[$key]);
	}
	public function valueToInt($value){
		return (int) $this->_baseService->numbersOnly($value);
	}	
	
	private function _validateCoupon(){
		foreach($this->_couponModel as $key){
				if(isset($this->_couponDetails[$key])){
					if(empty($this->_couponDetails[$key])){
						if($key == "ceilingAmount" 
							&& $this->_couponDetails['couponAppliesTo'] == "FREESHIPPING"){
						}else{
							$this->_errorContainer['EMPTY'][] = $key; 
						}
					}
				}else if(!isset($this->_couponDetails[$key])){
					$this->_errorContainer['INVALID'][] = $key;
				}	
			}
			$discountCount = count($this->_couponDetails['discountVal']);
			$qualifierCount = count($this->_couponDetails['amountQualifier']);
			if($discountCount > 0 &&  $qualifierCount > 0	
				&& $discountCount == $qualifierCount 
				&& $this->_couponDetails['couponAppliesTo'] != "FREESHIPPING"){
					$discountValErrorCounter = 0;
					
					foreach($this->_couponDetails['discountVal'] as $discount){
						if($discount == 0){
							$this->_errorContainer['EMPTY'][] = 'discount value/s';
						}/*else if($discount > (int) $this->_couponDetails['ceilingAmount'] && $this->_couponDetails['type'] == "percent"){
							$this->_errorContainer['INVALID'][] = 'Discount value/s should not be greater than the ceiling amount';
						}*/else if(isset($this->_couponDetails['type']) 
						&& $this->_couponDetails['type'] == "percent" && $discount > 100){
							$this->_errorContainer['INVALID'][] = 'discountVal';
						}
					}
				
					
					
			}
	}
	
	private function _isDateValid($date){
		try{
		if (substr_count($date, '/') == 2) { 
			list($m, $d, $y) = explode('/', $date);
			return checkdate($m, $d, sprintf('%04u', $y));
		} 
		return false;
		}catch(Exception $e){
			Throw new Exception($e->getMessage());
		}
	}
	
	private function _isTimeValid($time){
		$pattern = "/^(?:0[1-9]|1[0-2]):[0-5][0-9] (am|pm|AM|PM)$/";
		 if(preg_match($pattern,$time)){
		   return true;
		 }
		 return false;
	}

	private function _isPastDate($date){
		if(strtotime($date) >= strtotime(date("m/d/Y"))){
			 return false;
		}
		return true;
	}
	
	private function _isMonthValid($month){
		$months = $this->_couponFormModel->getMonths();
		$month = trim($month);
		if(isset($months[$month])){
			return true;
		}
		return false;
	}
	
	private function _isYearValid($year){
		$years = $this->_couponFormModel->getYears();
		$year = trim($year);
		if(isset($years[$year])){
			return true;
		}
		return false;
	}
	
	private function _numberOfMonthsValid($numberOfMonths){
		$getNumberOfMonthsArray = $this->_couponFormModel->getNumberOfMonths();
		$numberOfMonths = trim($numberOfMonths);
		if(isset($getNumberOfMonthsArray[$numberOfMonths])){
			return true;
		}
		return false;
	}
	
	private function _datesValid($startDate, $endDate, $startMin = "", $endMin = ""){
		try{
			if($startMin != "" && $endMin != ""){
				$startDate .= " " . $startMin;
				$endDate .= " " . $endMin;
			}
			$startDateToTime 	= strtotime($startDate);
			$endDateToTime 		= strtotime($endDate);
			
			if($startDateToTime > $endDateToTime || $startDateToTime == $endDateToTime){
				return false;
			}
			return true;
		}catch(Exception $e){
			return false;
		}
	}
	
	private function _searchCoupon($couponCode){
		if($couponCode != ""){
			$query = array(
					'$or' => array(
						array('coupon.code' => strtoupper(trim($couponCode))),
						array('coupon.name' => strtoupper(trim($couponCode))),
					)
				);
			$record = $this->_baseService->search($query, "coupon", true);
		}
		
		return isset($record) ? $record : array();
	}
	
	public function getDocument(){
		try{
			$this->_documentDetails['coupon'] = $this->_couponDetails;
			//$this->_expirationDetails['status'] = "unused";
			$this->_documentDetails['expiration'] = $this->_expirationDetails;
			
			//backend validation here
			$this->_validateCoupon();
			if($this->_action == "create"){
				$this->_documentDetails['createdAt'] 	= time();
				$this->_documentDetails['creator'] 	= $this->_username;
				$this->_documentDetails['createdvia'] 	= "ui";
			}else{
                                $this->_documentDetails['createdAt'] = $this->_formValues['createdAt'];
                                $this->_documentDetails['creator'] = $this->_formValues['creator'];
                                $this->_documentDetails['createdvia'] = $this->_formValues['createdvia'];
				$this->_documentDetails['updatedAt'] 	= time();
				$this->_documentDetails['modifiedby'] 	= $this->_username;
				$this->_documentDetails['modified'] 	= ($this->_documentDetails['status'] == "published") ? false : true;
			}
			
			if(count($this->_errorContainer) > 0){
				return array('error' => $this->_errorContainer);
			}else{
				return $this->_documentDetails;
			}
			
		}catch(Exception $e){
			throw new Exception($e->getMessage());
		}
	}
	
}
