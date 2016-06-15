<?php

/**
 * @category USAPTool_Modulestegory USAPTool_Modules
 * @package Dcms_Service
 * @copyright 2012
 * @author gconstantino
 * @version $Id$
 */
class Dcms_Service_Otuexportworker extends USAP_Service_ServiceAbstract {
	/**
     *
     * @var Zend_Config 
     */
    protected $_config;

    /**
     *
     * @var Dcms_Model_Form
     */
    protected $_couponFormModel;

    /**
     *
     * @var type 
     */
    protected $_username;

    /**
     *
i     * @var type 
     */
    public $_baseService;
    protected $_counponDetails;
    protected $_couponWhereClause;
	
	protected $_gearmanWorker;
	protected $_gearmanClient;

	protected $_oneTimeUseLimit;
	protected $_gearmanExportStatusCollection;
	
    /**
     *
     * @param Zend_Config $config
     * @param Dcms_Service_Base $base
     */
    public function __construct(Zend_Config $config, Dcms_Service_Base $base, 
		GearmanClient $client, 
		GearmanWorker $worker, 
		Zend_Application_Resource_ResourceAbstract $mongoMultiDbResource) {
		$this->_mongoResource = $mongoMultiDbResource; 
		$this->_gearmanClient = $client;
		$this->_gearmanWorker = $worker;
        $this->_baseService = $base;
        $this->_couponFormModel = new Dcms_Model_CouponValues();
        $this->_config = $config;
        $this->_couponDetails = array();
		$this->_couponWhereClause = array();
		$this->_gearmanExportStatusCollection = $config->coupon->collection->exportstatus;
		$this->_oneTimeUseLimit = (int) $config->onetimeuse->limit;
		$this->_path = $config->onetimeuse->path;
    }
	
    /**
     *
     * @return GearmanWorker 
     */
    public function worker() {
        return $this->_gearmanWorker;
    }

    /**
     * Registers a function with the name and function callback
     * 
     * @param type $function_name
     * @param type $callback 
     */
    public function addWorkerFunction($function_name, $callback) {
        $this->_gearmanWorker->addFunction($function_name, array($this, $callback));
    }
	
    private function _getCollection($collection) {
        $mongodb = $this->_mongoResource->getAdapter('coupon')->getDatabase();
		return $mongodb->selectCollection($collection);
    }
	
	public function exportCoupon($batchId){
		try{
			$data = array("coupon.name" => $batchId); 
			$findCoupons 	=  $this->_baseService->hydraConnect($data, "coupon", "read", "live");
			$couponData 	=  $this->_baseService->getRecords($findCoupons);
			
			$timeval = time();
			header("Content-Type: text/csv; filename=download-{$timeval}.csv");
			header('Expires: Mon, 29 Nov 1999 23:32:20 +0000 GMT');
			$hWriter = new Csv_Echoer(new Csv_Dialect_Csv());
			$header = $this->_getHeader();
			echo $hWriter->writeRow($header);

			/**
			*	get coupon document of a single one time use coupon
			*/
			$couponModel = $this->_mapCouponRowData($couponData[0]);
			foreach($couponData as $coupon){
					$row = $couponModel;
					$row['code'] = isset($coupon['coupon']['code']) ? $coupon['coupon']['code'] : "";
					$row['publish status'] = isset($coupon['status']) ? $coupon['status'] : "";
					
					echo $hWriter->writeRow(array_values($row));
			}
			exit;
		}catch(Exception $e){
			return array('error' => $e->getMessage());;
		}	
	}
	
	public function couponExportWorker($job){
		try{
			$params = unserialize($job->workload());
			$batchId = $params['coupon.name'];
			$total = $params['total'];
			$filename = $this->_path . $batchId . ".csv";
			echo "starting \n";
			echo date("m/d/Y h:i:s a") . "\n";
			$statusCollection = $this->_getCollection($this->_gearmanExportStatusCollection);
			$csvWriter = new Csv_Writer($filename);
			
			//code	batch name	discount value	amount qualifier	ceiling amount	type	appeasement	coupon applies to	class	owner	website	expiration status
			
			$header = $this->_getHeader();
			$csvWriter->writeRow($header);
			
			$offset = 0;
			$limit = $this->_oneTimeUseLimit;
			
			$statusDocument = array(
							'worker'        => "otuExportWorker",
							'batch_name'    => $batchId,
							'start_time'    => new MongoDate(),
							'total'         => $total,
							'numerator'     => $counter,
							'status'        => "ongoing"
				);
			$statusCollection->update(
				array('worker' => "otuExportWorker"), array('$set' => $statusDocument), array('upsert' => true)
			);
			
			/**
			get coupon document of a single one time use coupon
			*/
			$couponModel = $this->_mapCouponRowData($this->_getSingleCouponDocument($batchId));
			
			do{
				$data['data'] = array(
					'query' 	=> array('coupon.name' => $batchId),
					'limitskip' => 
						array(
							'skip'	=> $offset,
							'limit' => $limit,
						)
					); 
				
				$findCoupons 	=  $this->_baseService->hydraConnect($data, "coupon", "read", "live");
				$total 			=  $this->_baseService->getRecordCount($findCoupons);
				$couponData 	=  $this->_baseService->getRecords($findCoupons);

					foreach($couponData as $coupon){
						$row = $couponModel;
						$row['code'] 			= isset($coupon['coupon']['code']) ? $coupon['coupon']['code'] : "";
						$row['publish status'] 	= isset($coupon['status']) ? $coupon['status'] : "";
						$csvWriter->writeRow($row);
					}
				$offset += $limit;
				$statusDocument = array(
						'numerator'     => $offset,
						'total'     	=> $total,
					);
					$statusCollection->update(
						array('worker' => "otuExportWorker"), array('$set' => $statusDocument), array('upsert' => true)
					);
				echo "$offset/$total created \n";
			}while($offset < $total);
			
			echo "$total/$total created \n";
			echo date("m/d/Y h:i:s a") . "\n";
			
			echo "end \n";
			$statusDocument = array(
					'numerator'     => $offset,
					'end_time'    => new MongoDate(),
					'status'     	=> "done",
				);
				$statusCollection->update(
					array('worker' => "otuExportWorker"), array('$set' => $statusDocument), array('upsert' => true)
				);
			
		} catch (Exception $e) {
				$statusDocument = array(
					'numerator'     => $offset,
					'end_time'      => new MongoDate(),
					'status'     	=> "done",
					'message'		=> $e->getMessage(),
				);
				$statusCollection->update(
					array('worker' => "otuExportWorker"), array('$set' => $statusDocument), array('upsert' => true)
				);
			 echo "error: " . $e->getMessage() . "\n";

			$job->sendFail();
		}
		
	}
	
	private function _mapCouponRowData($coupon){
		$getOwner = $this->_baseService->getOwner($coupon['coupon']['owner']);
		$getClass = $this->_couponFormModel->getApplyCouponTo();
		$row = array();
			$row['code'] 				= isset($coupon['coupon']['code']) ? $coupon['coupon']['code'] : "";
			$row['batch name'] 			= isset($coupon['coupon']['name']) ? $coupon['coupon']['name'] : "";
			$row['discount value'] 		= isset($coupon['coupon']['discountVal']) ? implode(", ", $coupon['coupon']['discountVal']) : "";
			$row['amount qualifier'] 	= isset($coupon['coupon']['amountQualifier']) ? implode(", ", $coupon['coupon']['amountQualifier']) : "";
			$row['ceiling amount'] 		= isset($coupon['coupon']['ceilingAmount']) ? $coupon['coupon']['ceilingAmount'] : "";
			$row['type'] 				= isset($coupon['coupon']['type']) ? $coupon['coupon']['type'] : "";
			$row['appeasement'] 		= isset($coupon['coupon']['appeasement']) ? (($coupon['coupon']['appeasement']) ? "yes" : "no") : "";
			$row['coupon applies to'] 	= isset($coupon['coupon']['couponAppliesTo']) ? $coupon['coupon']['couponAppliesTo'] : "";
			$row['class'] 				= isset($getClass[$coupon['coupon']['class']]) ? $getClass[$coupon['coupon']['class']] : "";
			$row['owner'] 				= isset($getOwner['owner']) ? $getOwner['owner'] : "";
			$row['website'] 			= isset($coupon['coupon']['domains']) ? implode("", $coupon['coupon']['domains']) : "";
			$row['expiration status'] 	= isset($coupon['expiration']['status']) ? $coupon['expiration']['status'] : "";
			$row['publish status'] 		= isset($coupon['status']) ? $coupon['status'] : "";
		return $row;
	}
	
	private function _getSingleCouponDocument($batchId){
		$data['data'] = array(
				'query' 	=> array('coupon.name' => $batchId),
				'limitskip' => 
					array(
						'skip'	=> 0,
						'limit' => 1,
					)
				); 
		$findCoupons 	=  $this->_baseService->hydraConnect($data, "coupon", "read", "live");
		$couponData 	=  $this->_baseService->getRecords($findCoupons);
		return $couponData[0];
	}
	
	private function _getHeader(){
		return array(
			'code',
			'batch name', 
			'discountvalue', 
			'amount qualifier',
			'ceiling amount',
			'type',
			'appeasement',
			'coupon applies to',
			'class',
 			'owner',
			'website',			
			'expiration status',
			'publish status'
		);
	}

	
	
}

?>

