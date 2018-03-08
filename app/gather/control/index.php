<?php
namespace app\gather\control;
use dog\Control;
use dog\pattern\CalculatorSETime;
use dog\db\Db;
use dog\Config;
class Index extends Control{
	
	private $user_id = '';
	
	/**
	 * 导入SEO数据
	 */
	public function import(){
		require(LIB_PATH . 'phpExcel/PHPExcel.php');
		$excel_reader = new \PHPExcel_Reader_Excel5();
		// $filename     = $result['pathname'];
		$filename     = ROOT_PATH.'keywords.xls';
		if (!$excel_reader->canRead($filename)) {
			echo '导入ABC关键字，上传错误的文件类型: ' . $filename;exit;
		}
		
		$field_arr = array(
				'A' => 'keyword',
			  /*'B' => 'traffic'
				'C' => 'meta_keyword',
				'D' => 'meta_description',*/
		);
		
		$excel         = $excel_reader->load($filename);
		$sheet         = $excel->getSheet(0);
		$all_row       = $sheet->getHighestRow();
		$keys          = array_keys($field_arr);
		$all_column    = array_pop($keys);
		
		if ($all_row < 2) {
			echo '文件没有数据！';exit;
		}
		$datas = array();
		for ($row = 2; $row <= $all_row; $row++) {
			$data = array();
			for ($column = 'A'; $column <= $all_column; $column++) {
				$data[$field_arr[$column]] = trim($sheet->getCell($column . $row)->getValue());
			}
			$data['keyword'] = strtolower(trim(preg_replace('/\s+/',' ',$data['keyword'])));
			$data['keyword'] = str_replace("'", "\'", $data['keyword']);
			$datas[] = $data;
		}

		$db = new Db();
		foreach ($datas as $value){
			if(!$db->execute("insert into adwords_seo(keywords) values('{$value['keyword']}')")){
				echo 'Error: ' . mysqli_connect_error();
			}
		}

	}
	
	private function magic(){
		$db = new Db();
		$info = $db->selectInfo('select * from users where enabled=1 and cycle_time < 10000 order by cycle_time desc limit 1');
		
		if(empty($info)){
			echo 'no account!';exit;
		}
		$this->user_id = $info['id'];
		$adUsers = array(
				'developerToken' => $info['developerToken'],
				'clientCustomerId' => $info['clientCustomerId'],
				'OAUTH2' => array(
						'client_id' => $info['client_id'],
						'client_secret' => $info['client_secret'],
						'refresh_token' => $info['refresh_token']
				)
		);
		
		// Get AdWordsUser from credentials in "../auth.ini"
		// relative to the AdWordsUser.php file's directory.
		$user = new \AdWordsUser(null,$adUsers['developerToken'],null, $adUsers['clientCustomerId'],null, $adUsers['OAUTH2']);
		// Log every SOAP XML request and response.
		$user->LogAll();
		$this->_requestAdwords($user);
	}
	
	/**
	 * 抓取seo关键词在adwords的预估流量值
	 */
	public function adwords(){
		//导入账户信息
		$db  = new Db();
		$adwords = (new Config('adwords'))->getData();
		foreach ($adwords['adwords.clientCustomerId'] as $k=>$v){
			$clientCustomerId = $v;
			$client_id = $adwords['adwords.client_id'][$k];
			$client_secret = $adwords['adwords.client_secret'][$k];
			$refresh_token = $adwords['adwords.refresh_token'][$k];
			$developerToken = $adwords['adwords.developerToken'][$k];
			$enabled = $adwords['adwords.enabled'][$k];
			$db->execute("insert into users(clientCustomerId,client_id,client_secret,refresh_token,developerToken,enabled) ".
					" values('{$clientCustomerId}','{$client_id}','{$client_secret}','{$refresh_token}','{$developerToken}','{$enabled}') ON DUPLICATE KEY UPDATE enabled=VALUES(enabled) ");
		}
				
		//new \SoapServer(null, array('uri'=>"http://127.0.0.1/"));exit;
		//echo phpinfo();exit;
		// Include the initialization file
		$depth = '/../../../';
		define('SRC_PATH', dirname(__FILE__) . $depth . 'lib/');
		define('LIB_PATH_G', 'Google/Api/Ads/AdWords/Lib');
		define('UTIL_PATH', 'Google/Api/Ads/Common/Util');
		define('ADWORDS_UTIL_PATH', 'Google/Api/Ads/AdWords/Util');
		define('ADWORDS_UTIL_VERSION_PATH', 'Google/Api/Ads/AdWords/Util/v201702');
		
		define('ADWORDS_VERSION', 'v201702');
		
		// Configure include path
		ini_set('include_path', implode(array(
			ini_get('include_path'), PATH_SEPARATOR, SRC_PATH
		)));
		
		// Include the AdWordsUser
		require_once LIB_PATH_G . '/AdWordsUser.php';
		require_once UTIL_PATH . '/MapUtils.php';
		
		try{
			$this->magic();
		} catch (\Exception $e) {
			$this->magic();
			//切换adwords账号
			//[RateExceededError <rateName=RATE_LIMIT, rateKey=null, rateScope=ACCOUNT, retryAfterSeconds=30>]
			echo "Change the account\n";
			printf("An error has occurred: %s\n", $e->getMessage());
		}
		
		
	}
	
	public function _requestAdwords($user){
		$date = date('Y-m-d');
		$db  = new Db();
		$page_size = 500;
		while ( !empty($data = $db->select("select id as keyword_id,keywords as keyword from adwords_seo where result=0 limit $page_size ")) ){
			$result = $this->_getKeywordIdeasExample($user,$data);

			$user_info = $db->selectInfo('select * from users where id='.$this->user_id);
			if($user_info['last_date'] == date('Y-m-d')){
				$db->execute("update users set cycle_time= cycle_time+1 where id=".$this->user_id);
			}else{
				$db->execute("update users set cycle_time= 1,last_date='".date('Y-m-d')."' where id=".$this->user_id);
			}
			
			 if($result['status'] === 200){
				foreach ($result['data'] as $value){
					$monthly_multi_sql = '';
					if(!empty($value['monthly_arr'])){
						for($m=1;$m<=count($value['monthly_arr']);$m++){
							$monthly_multi_sql.=','.'historical_data_'.$m.'='.$value['monthly_arr'][$m-1];
						}
					}
					$db->execute("update `adwords_seo` set `month_avg`='{$value['month_avg']}', `remarks`='{$value['remarks']}',`value`= {$value['lately_month_volume']},`result`=1,`day`='{$date}'".$monthly_multi_sql." where `id`={$value['keyword_id']} ");
				}
				
				if(!empty($result['keyowrds_ids'])){
					$db->execute("update `adwords_seo` set `result`=1 where `id` in(".$result['keyowrds_ids'].") ");
				}
			}
			sleep(30);
			unset($result,$data);
		}
		unset($db,$page,$page_size,$date);
	}
	
	
	
	/**
	 * Runs the example.
	 * @param AdWordsUser $user the user to run the example with
	 */
	public function _getKeywordIdeasExample(\AdWordsUser $user,$db_keywords = array()) {
		if(empty($db_keywords)) return array('status' => 0,'data' => '$db_keywords is null .\n');
		// Get the service, which loads the required classes.
		$targetingIdeaService =
		$user->GetService('TargetingIdeaService', ADWORDS_VERSION);
	
		// Create selector.
		$selector = new \TargetingIdeaSelector();
		$selector->requestType = 'STATS';
		$selector->ideaType = 'KEYWORD';
		$selector->requestedAttributeTypes = array('KEYWORD_TEXT', 'SEARCH_VOLUME',
				'CATEGORY_PRODUCTS_AND_SERVICES','TARGETED_MONTHLY_SEARCHES');
	
		// Create seed keyword.
		$keyword_array = array();
		$keyword_id_array = array();
		$keyword_ids = array();
		foreach ($db_keywords as $value){
			$keyword_array[] = $value['keyword'];
			$keyword_id_array[strtolower($value['keyword'])] = $value['keyword_id'];
			$keyword_ids[] = $value['keyword_id'];
		}
		// Create related to query search parameter.
		$relatedToQuerySearchParameter = new \RelatedToQuerySearchParameter();
		$relatedToQuerySearchParameter->queries = $keyword_array;
		$selector->searchParameters[] = $relatedToQuerySearchParameter;
	
		// Create language search parameter (optional).
		// The ID can be found in the documentation:
		//   https://developers.google.com/adwords/api/docs/appendix/languagecodes
		// Note: As of v201302, only a single language parameter is allowed.
		$languageParameter = new \LanguageSearchParameter();
		$english = new \Language();
		$english->id = 1000;
		$languageParameter->languages = array($english);
		$selector->searchParameters[] = $languageParameter;
	
		// Create network search parameter (optional).
		$networkSetting = new \NetworkSetting();
		$networkSetting->targetGoogleSearch = true;
		$networkSetting->targetSearchNetwork = false;
		$networkSetting->targetContentNetwork = false;
		$networkSetting->targetPartnerSearchNetwork = false;
	
		$networkSearchParameter = new \NetworkSearchParameter();
		$networkSearchParameter->networkSetting = $networkSetting;
		$selector->searchParameters[] = $networkSearchParameter;
	
		// Set selector paging (required by this service).
		$selector->paging = new \Paging(0,700);
	
	
		// Make the get request.
		$page = $targetingIdeaService->get($selector);
		$data_array = array();
		$lately_monthly_volume = array();//key[year,month,count]
		// Display results.
		if (isset($page->entries)) {
			foreach ($page->entries as $targetingIdea) {
				$data = \MapUtils::GetMap($targetingIdea->data);
				$keyword = $data['KEYWORD_TEXT']->value;
				$search_volume = isset($data['SEARCH_VOLUME']->value) ? $data['SEARCH_VOLUME']->value : 0;
				if ($data['CATEGORY_PRODUCTS_AND_SERVICES']->value === null) {
					$categoryIds = '';
				} else {
					$categoryIds =
					implode(', ', $data['CATEGORY_PRODUCTS_AND_SERVICES']->value);
				}
	
				$month_arr = array();
				$monthly_searches = '';
				$monthly_arr = array();
				if($data['TARGETED_MONTHLY_SEARCHES']->value !== null){
					foreach ($data['TARGETED_MONTHLY_SEARCHES']->value as $info) {
						$monthly_arr[] = $info->count;
						$monthly_searches.=$info->year.'-'.$info->month.':'.$info->count.';';
						$month_arr[] = $info->count;
					}
				}
				$month_average  = empty($month_arr)?0:array_sum($month_arr)/count($month_arr);
				if(!empty($data['TARGETED_MONTHLY_SEARCHES']->value[0]->count)){
					unset($keyword_ids[$keyword_id_array[strtolower($keyword)]]);
				}
				
				$data_array[] = array('monthly_arr'=>$monthly_arr, 'month_avg'=>$month_average,'remarks' => $monthly_searches, 'keyword' => $keyword,'lately_month_volume'=> empty($data['TARGETED_MONTHLY_SEARCHES']->value[0]->count)?0:$data['TARGETED_MONTHLY_SEARCHES']->value[0]->count,'monthly_searches' => $monthly_searches,'keyword_id' => $keyword_id_array[strtolower($keyword)]);
			}
			return array('status' => 200,'keyowrds_ids'=>implode(',', $keyword_ids),'data' => $data_array);
		} else {
			return array('status' => 0,'keyowrds_ids'=>implode(',', $keyword_ids),'data' => "No keywords ideas were found.\n");
		}
	}	
}