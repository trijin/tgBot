<?php
/**
* tgBot - simple class for writing bots
* @link -
* @author trijin
* @copyright 2016 trijin
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
* @use vrana/notorm:dev-master, guzzlehttp/guzzle:~6.0
*/
class tgBot {
	private $APIKEY=false;
	private $APIURL=false;
	private $botid=false;
	private $botTag=false;
	private $db=false;
	private $log2db=false;
	private $cache=false;
	private $Gc=false;

	private $comands=array();
	private $masks=array();

	private $config=array('db'=>false,'log2db'=>false,'cache'=>false);


	function __construct($botKey=false){
		if($botKey===false) {
			// Нет ключа.
		} elseif( preg_match('/^(\d+):[A-Za-z0-9\-_]{35}$/', $botKey, $m)) {
			// Нормальный ключ
			$this->APIKEY=$botKey;
			$this->APIURL='https://api.telegram.org/bot'.$botKey.'/';
			$this->botid=$m[1];
		} else {
			// ключ левый.
			$this->APIKEY=$botKey;
			$this->APIURL='https://api.telegram.org/bot'.$botKey.'/';
		}
		if($this->APIURL) {
			$this->Gc=new GuzzleHttp\Client(['base_uri' => $this->APIURL,'verify'=>false,'http_errors'=>false]);
		}
	}
	function config($params) {
		foreach ($params as $key => $value) {
			if(isset($this->config[$key])) {
				$this->$key=$value;
			}
		}
	}
	function sendHTML($ch,$text,$params=array()){
		$params['parse_mode']='HTML';
		$this->send($ch,$text,$params);
	}
	function send($ch,$text,$params=array()){
		$array=is_array($params)?$params:array();

		$array['chat_id']=$ch;
		$array['text']=$text;
		$a=$this->Gc->post('sendMessage',['form_params'=>$array]);
		$code = $a->getStatusCode();
		if($code>=200 && $code<500) {
			$body=$a->getBody();
			$ans=new tgBotMessage($body);
			if($this->log2db && $this->db) {
				$this->save($ans);
			}
			return $ans;
		} else {
			// var_export($a);
			return false;
		}
	}
	function sendPhoto($ch,$img,$text=false,$params=array()){
		return $this->sendImg($ch,$img,$text,$params);
	}
	function sendImg($ch,$img,$text=false,$params=array()){
		$multipart=array();
		if(is_array($params)) {
			foreach ($params as $key => $value) {
				$multipart[]=array(
					'name'=>$key,
					'contents'=>json_encode($value)
					);
			}
		}
		if($text!==false) {
			// caption
			$multipart[]=array(
				'name'=>'caption',
				'contents'=>$text
				);
		}

		if(strlen($img)<1000 && is_file($img)) {
			//photo
			$multipart[]=array(
				'name'=>'photo',
				'contents'=>fopen($img, 'r')
				);
		} elseif(strlen($img)>1000) {
			// меньше тысячи за файл не считаем.
			$multipart[]=array(
				'name'=>'photo',
				'contents'=>$img,
				'filename' => 'file'.date('ymdHis').'.png',
				);			
		} else {
			return false;
		}
		$multipart[]=array(
			'name'=>'chat_id',
			'contents'=>$ch
			);
		$a=$this->Gc->post('sendPhoto',['multipart' => $multipart]);
		$code = $a->getStatusCode();
		if($code>=200 && $code<500) {
			$body=$a->getBody();
			$ans=new tgBotMessage($body);
			if($this->log2db && $this->db) {
				$this->save($ans);
			}
			return $ans;
		} else {
			var_export($a);
		}
	}

}

class tgBotMessage {
	private $data;
	function __construct($json) {
		$this->data=json_decode($json,true);
	}
	function __toString() {
		return json_encode($this->data);
	}
	function getChannelID() {
		if(isset($this->data['message']['chat']['id'])) {
			return $this->data['message']['chat']['id'];
		} elseif(isset($this->data['callback_query']['message']['chat']['id'])) {
			return $this->data['callback_query']['message']['chat']['id'];
		} else {
			return false;
		}
	}
	function getUserID() {
		if(isset($this->data['message']['from']['id'])) {
			return $this->data['message']['from']['id'];
		} elseif(isset($this->data['callback_query']['message']['from']['id'])) {
			return $this->data['callback_query']['message']['from']['id'];
		} else {
			return false;
		}
	}
	function error() {
		if($data['ok']) {
			return false;
		} elseif(isset($data['description']) && isset($data['error_code'])) {
			return 'Code: '.$data['error_code'].' '.$data['description'];
		}
	}
}