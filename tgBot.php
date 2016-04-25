<?php
/**
* tgBot - simple class for writing bots
* @link -
* @author trijin
* @copyright 2016 trijin
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
* @use trijin/notorm:dev-master, guzzlehttp/guzzle:~5.3.0
*/
class tgBot {
	const BOT_PREG_MATCH=1;
	const BOT_SIMPLE_MATCH=2;
	const BOT_STARTWITH=3;
	private $matchKeys=array('start','equal','preg','in');
	private $APIKEY=false;
	private $APIURL=false;
	private $botid=false;
	private $botTag=false;
	private $db=false;
	private $log2db=false;
	private $cache=false;
	private $Gc=false;
	private $me=false;
	private $codepage='cp1251'; // personal language coz utf8 not recognized in preg_ functions

	private $matches=array();
	private $on=array();

	private $config=array('db'=>false,'log2db'=>false,'cache'=>false,'codepage'=>'cp1251');


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
			$this->Gc=new GuzzleHttp\Client(array('base_uri' => $this->APIURL,'verify'=>false,'http_errors'=>false));
		}
	}
	function config($params) {
		foreach ($params as $key => $value) {
			if(isset($this->config[$key])) {
				$this->$key=$value;
			}
		}
		if($this->cache!==false) {
			if(is_dir(realpath($this->cache))) {
				$this->cache=realpath($this->cache).'/';
			} else {
				if(mkdir($this->cache,0755,true)) {
					$this->cache=realpath($this->cache).'/';
				} else {
					$this->cache=false;
				}
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
		}/* else {
			var_export($a);
		}*/
	}
	function comand() {
		$params=func_get_args();
		if(count($params)<2) {
			throw new ErrorException("minimum 2 parameters");
		}
		if(!is_callable($params[count($params)-1])) {
			throw new ErrorException("Last parameter must be function");
		}

		if(!is_array($params[0])) {
			if(in_array($params[1],$this->matchKeys)) {
				$params[0]=array($params[1]=>$params[0]);
				unset($params[1]);
				$params=array_values($params);
			} else {
				$params[0]=array('start'=>$params[0]);
			}
		}

		$this->matches[]=$params;
		return $this;

	}
	function on($on,$funct) {
		if(!is_callable($funct)) {
			throw new ErrorException("Last parameter must be function");
		}
		$on=(array)$on;
		$noOn=true;
		foreach ($on as $value) {
			if(in_array($value, array('audio','document','photo','sticker','video','voice','contact','location','venue','new_chat_member','left_chat_member','new_chat_title','new_chat_photo','delete_chat_photo','group_chat_created','supergroup_chat_created','channel_chat_created','pinned_message','migrate_to_chat_id','migrate_from_chat_id','callback_query','message','inline_query','chosen_inline_result','forward'))) {
				$this->on[$value][]=$funct;
				$noOn=false;
			}
		}
		if($noOn) {
			throw new ErrorException("wrong On parametr");
		}
		return $this;
	}

	function stop() {
		throw new \tgBot\Exception\Stop();
	}
	function pass() {
		throw new \tgBot\Exception\Pass();
	}
	function getBotUsername() {
		if(isset($this->me['username'])) {
			return $this->me['username'];
		}

		if($this->cache!==false && is_file($this->cache.'me'.$this->botid.'.botinfo')) {
			$this->me=json_decode(file_get_contents($this->cache.'me'.$this->botid.'.botinfo'),true)['result'];
		}

		if(isset($this->me['username'])) {
			return $this->me['username'];
		}

		$a=$this->Gc->post('getMe');

		if($this->cache!==false) {
			file_put_contents($this->cache.'me'.$this->botid.'.botinfo',$a->getBody());
		}
		$this->me=json_decode($a->getBody(),true)['result'];

		if(isset($this->me['username'])) {
			return $this->me['username'];
		}
	}
	function matching($comand,$text,$message) {
		$BotUsername=$this->getBotUsername();
		$forBot=$message->forBot();

		if($forBot && $BotUsername) {
			if($BotUsername!=$forBot) {
				return false;
			}
		}
		if($BotUsername) {
			$text=preg_replace('#^(/\w+)\@'.$BotUsername.'(\s[\n\s\S]*)?$#', '$1$2', $text);
		}
		$match=false;
		foreach ($comand[0] as $type => $string) {
			switch ($type) { //'start','equal','preg','in'
				case 'start':
					if(substr($text, 0,strlen($string))===(string)$string) {
						$match=true;
					}
					break;
				case 'equal':
					if((string)$text===(string)$string) {
						$match=true;
					}
					break;
				case 'preg':
					if(preg_match($string, $text,$preg_matches)) {
						$match=true;
					}
					break;
				case 'in':
					if(strpos($text, $string)!==false) {
						$match=true;
					}
					break;

			}
			if($match===true) {
				$funct=array_pop($comand);
				if($type=='preg') {
					$funct($message,$text,$preg_matches);
				} else {
					$funct($message,$text);
				}
				break;
			}
		}
		return $match;


	}
	function run($message) {
		$text=$message->getText();
		try {
			if($text!==false && strlen($text)>0 && count($this->matches)>0) {
				foreach ($this->matches as $comand) {
					try {

						$this->matching($comand,$text,$message);

					} catch (\tgBot\Exception\Pass $e) {
						continue;
					}
				}

			}

			foreach ($this->on as $key => $value) {
				if($message->is($key)) {
					//run Вася run
					foreach ($value as $funct) {
						try {
							if(is_callable($funct)) {
								$funct($message);
							}
						} catch (\tgBot\Exception\Pass $e) {
							continue;
						}
					}
				}
			}

		} catch (\tgBot\Exception\Stop $e) {
			//all ok, just stop
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
	function getText() {
		if(isset($this->data['message']['text'])) {
			return $this->data['message']['text'];
		}elseif(isset($this->data['message']['caption'])) {
			return $this->data['message']['caption'];
		//}elseif(isset($this->data['inline_query']['query'])) {
		//	return $this->data['inline_query']['query'];
		} else return false;
	}
	function is($key) {
		if(in_array($key, array('callback_query','message','inline_query','chosen_inline_result')) && isset($this->data[$key])) {
			return true;
		}

		if(in_array($key, array('forward')) && isset($this->data['message']['forward_from'])) {
			return true;
		}

		if(isset($this->data['message'][$key])) {
			return true;
		}
	}
	function forBot() {
		if(preg_match('#^/\w+@(\w+bot)(\s[\n\s\S]*)?$#',$this->getText(),$m)) {
			$bot=$m[1];
		} else {
			$bot=false;
		}
		return $bot;
	}
}