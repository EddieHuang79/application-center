<?php

namespace App\Traits;

use Illuminate\Support\Facades\Session;
use App\logic\Record_logic;
use Illuminate\Pagination\LengthAwarePaginator;

trait SchemaFunc
{

	// 頁數處理

	protected function get_page()
	{

		$result = isset($_GET["page"]) ? (int)$_GET["page"] : 0;

		Session::put("page", $result);

		return $result;

	}


	// 取得搜尋變數

	protected function get_search_query()
	{

		$tmp = Session::get("searchParam");

		$tmp[$this->key] = isset($tmp[$this->key]) ? $tmp[$this->key] : array();

		$result = $tmp[$this->key];

		return $result;

	}


	// 取得麵包屑資料

	protected function get_path_info()
	{

		return json_encode( array( $this->action ) );

	}


	// 取得成功訊息

	protected function get_success_msg()
	{

		$result = Session::get( 'SuccessMsg' );

		Session::forget('SuccessMsg');

		return $result;

	}


	// 取得錯誤訊息

	protected function get_error_msg()
	{

		$result = Session::get( 'ErrorMsg' );

		Session::forget('ErrorMsg');

		return $result;

	}


	// 取得原始輸入資料

	protected function get_ori_data()
	{

		$result = Session::get( 'OriData' );

		Session::forget('OriData');

		return $result;

	}


	// 將schema設為只能讀取

	protected function readOnly( $data, $attr )
	{

		$result = '';

		if ( !empty($data) && !empty($attr) ) 
		{

			$tmp1 = json_decode($data, true);

			$tmp2 = json_decode($tmp1[$attr], true);

			foreach ($tmp2 as &$row) 
			{

				if ( in_array($row["type"], array(1, 4, 7, 12)) ) 
				{

					$row["type"] = 5;

				}

			}

			$tmp1[$attr] = json_encode($tmp2);

			$result = json_encode($tmp1);

		}

		return $result;

	}


	// 取得成功訊息

	protected function set_success_msg( $type = 1 )
	{

		$position = $type === 1 ? "新增" : "修改";

		$msg = $type === 1 ? $this->txt["create_success"] : $this->txt["edit_success"];

		$action = $this->action["name"] . "-" . $position;

		$content = json_encode($_POST);

		Record_logic::write_operate_log( $action, $content );

		Session::put( 'SuccessMsg', $msg );

	}


	// 取得錯誤訊息

	protected function set_error_msg( $type = 1, $e )
	{

		$position = $type === 1 ? "新增" : "修改";

		$error_result = $e->getMessage();

		$error_result = json_decode($error_result, true);

		$error_result["data"]["password"] = '';

		Session::put( 'ErrorMsg', $error_result["msg"] );

		Session::put( 'OriData', $error_result["data"] );

		$action = $this->action["name"] . "-" . $position;

		$content = json_encode($error_result);

		Record_logic::write_error_log( $action, $content );

	}


	// 整數轉時間

	protected function time_to_string( $number )
	{

		$result = '';

		if ( !empty($number) && is_int($number) ) 
		{

			$hour = (int)floor( $number / ( 60 * 60 ) ) ;

			$number = $hour > 0 ? $number - $hour * 60 * 60 : $number ;

			$minute = (int)floor( $number / 60 ) ;

			$number = $minute > 0 ? $number - $minute * 60 : $number ;

			$second = $number;

			$time = array(
				"hour" 	=> str_pad( $hour, 2, "0", STR_PAD_LEFT),
				"min"	=> str_pad( $minute, 2, "0", STR_PAD_LEFT),
				"sec"	=> str_pad( $second, 2, "0", STR_PAD_LEFT)
			);

			$result = implode(":", $time);
			
		}

		return $result;

	}


	// 		頁籤資料
	// 		@	https://arjunphp.com/laravel-5-pagination-array/

	protected function set_array_page( $data )
	{

		$result = array();

		if ( !empty($data) && is_array($data) ) 
		{

			// Get current page form url e.x. &page=1

			$currentPage = LengthAwarePaginator::resolveCurrentPage();

			// Create a new Laravel collection from the array data

			$itemCollection = collect( $data );

			// Define how many items we want to be visible in each page

			$perPage = 1;

			// Slice the collection to get the items to display in current page

			$currentPageItems = $itemCollection->slice(($currentPage * $perPage) - $perPage, $perPage)->all();

			// Create our paginator and pass it to the view

			$result = new LengthAwarePaginator($currentPageItems , count($itemCollection), $perPage);

			// set url path for generted links

			$result->setPath( url()->full() );
			
		}

		return $result;

	}


	// 		在陣列設定啟用文字

	protected function set_status_txt( $data )
	{

		$_this = new self();

		$result = array();

		if ( !empty($data) && is_array($data) ) 
		{

			$status_txt = [
				["id" => 1, "name" => __('base.enable')],
				["id" => 2, "name" => __('base.disable')]
			];
			
			$status_array = $_this->map_with_key( $status_txt, $key1 = "id", $key2 = "name" );

			$collection = collect( $data );

			$result = $collection->map(function ($item, $key) use( $status_array ) {

				$item->status = [
					"id" 	=> $item->status,
					"name" 	=> isset($status_array[$item->status]) ? $status_array[$item->status] : ''
				];

				return $item;

			})
			->all();

		}

		return $result;

	}


	// 		設定表頭文字

	protected function set_header_txt( $data, $txt )
	{

		$_this = new self();

		$result = array();

		if ( !empty($data) && is_array($data) && !empty($txt) && is_array($txt) ) 
		{

			$result = collect( $data )->pluck( "Field" )->filter(function ($value, $key) use( $txt ) {
			    return !empty($txt[$value]);
			})->reduce(function ($result, $item) use( $txt ) {
				$result = isset($result) ? $result : [] ;
				return array_merge( $result, [$item => $txt[$item]] );
			});

		}

		return $result;

	}


	// 		unique array handle

	protected function get_unique_array( $data, $key )
	{

		return collect( $data )
			->pluck( $key )
			->unique()
			->values()
			->toArray();

	}


	// 		map array handle

	protected function pluck( $data, $key )
	{

		return collect( $data )
			->pluck( $key )
			->toArray();

	}


	// 		map group

	protected function map_to_groups( $data, $key1, $key2 )
	{

		return collect( $data )
			->mapToGroups(function ($item, $key) use( $key1, $key2 ) {
				$item = get_object_vars($item);
				return [$item[$key1] => $item[$key2]];
			})
			->toArray();

	}


	// 		map with key

	protected function map_with_key( $data, $key1, $key2 )
	{

		return collect( $data )
			->mapWithKeys(function ($item, $key) use( $key1, $key2 ) {
				$item = is_object($item) ? get_object_vars($item) : $item ;
				return [$item[$key1] => $item[$key2]];
			})
			->toArray();

	}


	// 		map with key assign default value

	protected function map_with_key_assign_default_value( $data, $key1, $default )
	{

		return collect( $data )
			->mapWithKeys(function ($item, $key) use( $key1, $default ) {
				$item = is_object($item) ? get_object_vars($item) : $item ;
				return [$item[$key1] => $default];
			})
			->toArray();

	}


	// 		only return assign attribute

	protected function values( $data )
	{

		return collect( $data )
			->values()
			->toArray();

	}


	// 		simple set default value

	protected function set_default_value( $data, $default_value )
	{

		return collect( $data )
			->mapWithKeys(function ($item, $key) use( $default_value ) {
				return [$key => $default_value];
			})
			->toArray();

	}


	// 統一日期格式

	protected function get_string_date( $time )
	{

		$time = is_string($time) === true ? strtotime($time) : $time ; 

		$correct_time = $time > 0 ;

		return $correct_time === true ? date("Y-m-d", $correct_time) : '' ;

	}


	// 統一日期時間格式

	protected function get_now_datetime()
	{

		return date("Y-m-d H:i:s");

	}


	// 		取得page data

	protected function get_pagination_data( $data )
	{

		$result = [];

		if ( $data->isNotEmpty() ) 
		{

			$result = [
				"count"  		=> $data->count(),
				"currentPage"  	=> $data->currentPage(),
				"firstItem"  	=> $data->firstItem(),
				"hasMorePages"  => $data->hasMorePages(),
				"lastItem"  	=> $data->lastItem(),
				"lastPage"  	=> $data->lastPage(),
				"nextPageUrl"  	=> $data->nextPageUrl(),
				"onFirstPage"  	=> $data->onFirstPage(),
				"perPage"  		=> $data->perPage(),
				"previousPageUrl"  => $data->previousPageUrl(),
				"total"  		=> $data->total()
			];
			
		}

		return $result;

	}


	// 		password rule
	// 		至少一碼英文+數字
	// 		介於6-8碼

	protected function pwd_rule( $pwd )
	{

		$result = false;

		if ( !empty($pwd) && is_string($pwd) ) 
		{
			
			$result = preg_match("/[^A-Za-z\s]/i", $pwd) > 0 && 
						preg_match('/[^0-9\s]/i', $pwd) > 0 &&
						strlen($pwd) >= 6 && 
						strlen($pwd) <= 8 ? true : false ;
			
		}

		return $result;

	}


}