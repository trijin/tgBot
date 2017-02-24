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
	private $matchKeys=array('start','equal','preg','preg_a','in');
	private $APIKEY=false;
	private $APIURL=false;
	private $botid=false;
	public $id=false;
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

	function getConfig($param) {
		if(isset($this->$param)) {
			return $this->$param;
		}
	}
	function getChat($chatId) {

		$array['chat_id']=$chatId;

		$a=$this->Gc->post('getChat',['form_params'=>$array]);
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
	function getChatAdministrators($chatId) {

		$array['chat_id']=$chatId;

		$a=$this->Gc->post('getChatAdministrators',['form_params'=>$array]);
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
	function getChatMembersCount($chatId) {

		$array['chat_id']=$chatId;

		$a=$this->Gc->post('getChatMembersCount',['form_params'=>$array]);
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
	function getChatMember($chatId,$userId) {

		$array['chat_id']=$chatId;
		$array['user_id']=$userId;

		$a=$this->Gc->post('getChatMember',['form_params'=>$array]);
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
	function getUserProfilePhotos($userId) {

		$array['user_id']=$userId;

		$a=$this->Gc->post('getUserProfilePhotos',['form_params'=>$array]);
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
	function getFile($file) {
		$array['file_id']=$file;

		$a=$this->Gc->post('getFile',['form_params'=>$array]);
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
	function downloadFileByPath($file_path,$saveTo=false) {
		$a=$this->Gc->get($this->FILEURL.$file_path);
		$code = $a->getStatusCode();
		if($code>=200 && $code<500) {
			return $a->getBody();
		} else {
			return false;
		}
	}
	function __construct($botKey=false){
		if($botKey===false) {
			// Нет ключа.
		} elseif( preg_match('/^(\d+):[A-Za-z0-9\-_]{35}$/', $botKey, $m)) {
			// Нормальный ключ
			$this->APIKEY=$botKey;
			$this->APIURL='https://api.telegram.org/bot'.$botKey.'/';
			$this->FILEURL='https://api.telegram.org/file/bot'.$botKey.'/';
			$this->botid=$m[1];
			$this->id=$m[1];
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
		return $this->send($ch,$text,$params);
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
	function sendAction($ch,$type){
		$av=array('typing','upload_photo', 'record_video', 'upload_video', 'record_audio', 'upload_audio', 'upload_document', 'find_location');
		if(!in_array($type, $av)) {
			return false;
		}

		$array['chat_id']=$ch;
		$array['action']=$type	;
		$a=$this->Gc->post('sendChatAction',['form_params'=>$array]);
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
		// return $this->sendImg($ch,$img,$text,$params);
		return $this->sendFile($ch,'photo',$img,$text,$params,'photo.jpg');
	}
	function sendFile($ch,$type,$File,$text=false,$params=array(),$filename=false){

		$av=array(
			'audio'=>array('param'=>'audio','method'=>'sendAudio','deffile'=>'audio.mp3','text'=>'title'),
			'photo'=>array('param'=>'photo','method'=>'sendPhoto','deffile'=>'photo.jpg','text'=>'caption'),
			'document'=>array('param'=>'document','method'=>'sendDocument','text'=>'caption'),
			'sticker'=>array('param'=>'sticker','method'=>'sendSticker','deffile'=>'sticker.webp'),
			'video'=>array('param'=>'video','method'=>'sendVideo','deffile'=>'video.mp4','text'=>'caption'),
			'voice'=>array('param'=>'voice','method'=>'sendVoice','deffile'=>'voice.ogg'),
			);
		$type=strtolower($type);
		if(!isset($av[$type])) {
			return false;
		}
		if($filename===false && isset($av[$type]['deffile'])) {
			$filename=$av[$type]['deffile'];
		}
		$multipart=array();
		//'AgADAgAD2acxGyaVXAUagRIhIaSzqLtphCoABC3h8pKFF-TOGRkAAgI'

		if(is_array($params)) {
			foreach ($params as $key => $value) {
				$multipart[]=array(
					'name'=>$key,
					'contents'=>json_encode($value)
					);
			}
		}
		if($text!==false && isset($av[$type]['text'])) {
			// caption
			$multipart[]=array(
				'name'=>$av[$type]['text'],
				'contents'=>$text
				);
		}
		// if()
		if(is_resource($File) && get_resource_type($File)=='stream') {
			$multipart[]=array(
				'name'=>$av[$type]['param'],
				'contents'=>$File,
				);

		}elseif(strlen($File)<1000 && is_file($File)) {
			//photo
			$multipart[]=array(
				'name'=>$av[$type]['param'],
				'contents'=>fopen($File, 'r'),
				);
		} elseif(preg_match('#^[A-Za-z0-9\-_]{30,60}$#', $File)) {
			$multipart[]=array(
				'name'=>$av[$type]['param'],
				'contents'=>$File,
				);
		} elseif(strlen($File)>0) {
			// меньше тысячи за файл не считаем.
			$multipart[]=array(
				'name'=>$av[$type]['param'],
				'contents'=>$File,
				'filename'=>$filename
				);			
		} else {
			return false;
		}
		$multipart[]=array(
			'name'=>'chat_id',
			'contents'=>$ch
			);
		$a=$this->Gc->post($av[$type]['method'],['multipart' => $multipart]);
		$code = $a->getStatusCode();
		if($code>=200 && $code<500) {
			$body=$a->getBody();
			$ans=new tgBotMessage($body);
			if($this->log2db && $this->db) {
				$this->save($ans);
			}
			return $ans;
		} else return false;
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
				'filename'=>'photo.png'
				#TODO: Сделать определение файла из строки
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
			throw new ErrorException("minimum 2 arguments");
		}
		if(!is_callable($params[count($params)-1])) {
			throw new ErrorException("Last arguments must be function");
		}

		if(!is_array($params[0])) {
			if(is_string($params[1]) && in_array($params[1],$this->matchKeys)) {
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
			if(in_array($value, array('audio','document','photo','sticker','video','voice','contact','location','venue','new_chat_member','left_chat_member','new_chat_title','new_chat_photo','delete_chat_photo','group_chat_created','supergroup_chat_created','channel_chat_created','pinned_message','migrate_to_chat_id','migrate_from_chat_id','callback_query','message','inline_query','chosen_inline_result','forward','pinned','edited_message','edited'))) {
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
			$botinfo=json_decode(file_get_contents($this->cache.'me'.$this->botid.'.botinfo'),true);
			$this->me=$botinfo['result'];
		}

		if(isset($this->me['username'])) {
			return $this->me['username'];
		}

		$a=$this->Gc->post('getMe');

		if($this->cache!==false) {
			file_put_contents($this->cache.'me'.$this->botid.'.botinfo',$a->getBody());
		}

		$botinfo=json_decode($a->getBody(),true);
		$this->me=$botinfo['result'];

		if(isset($this->me['username'])) {
			return $this->me['username'];
		}
	}
	function sendPlace($ch,$array) {
		return $this->sendVenue($ch,$array);
	}
	function sendVenue($ch,$array) {
		$allParams=array('latitude','longitude','title','address','foursquare_id','disable_notification','reply_to_message_id','reply_markup');
		$mustParams=array('latitude'=>true,'longitude'=>true,'title'=>true,'address'=>true);
		$send=array('chat_id'=>$ch);

		foreach ($array as $key => $value) {
			if(isset($mustParams[$key])) {
				unset($mustParams[$key]);
			}
			if(in_array($key, $allParams)) {
				$send[$key]=$value;
			}
		}
		if(count($mustParams)>0) {
			return false;
		}
		$a=$this->Gc->post('sendVenue',['form_params'=>$send]);
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
		foreach ($comand[0] as $type => $string_a) {
			if(!is_array($string_a)) {
				$string_a=(array)$string_a;
			}
			foreach ($string_a as $string) {
				switch ($type) { //'start','equal','preg','in'
					case 'start':
						if(substr($text, 0,strlen($string))===(string)$string) {
							$match=true;
						}
						break;
					case 'equal':
						if(trim((string)$text)===trim((string)$string)) {
							$match=true;
						}
						break;
					case 'preg':
						if(preg_match($string, $text,$preg_matches)) {
							$match=true;
						}
						break;
					case 'preg_a':
						if(preg_match_all($string, $text,$preg_matches,PREG_SET_ORDER)) {
							if(count($preg_matches)>0) {
								$match=true;
							}
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
					if(in_array($type, array('preg','preg_a'))) {
						@$funct($message,$text,$preg_matches);
					} else {
						@$funct($message,$text);
					}
					break;
				}
				if($match===true) break;
			}
		}
		return $match;


	}
	function run($message) {
		if($this->log2db && $this->db) {
			$this->save($message);
		}
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
	function saveChats($chat) {
		if($chat) {
			$available=array('id','title','type','bot_id');
			foreach ($chat as $key => $value) {
				if(!in_array($key,$available)) {
					// $this->send(OWNER,'new chat key: '.$key.' = '.$value);
					unset($chat[$key]);
				}
			}
			$chat['bot_id']=$this->botid;
			$dbchat=$this->db->chat[array('id'=>$chat['id'],'bot_id'=>$chat['bot_id'])];
			if($dbchat) {
				if($chat['title']!=$dbchat['title'] || $chat['type']!=$dbchat['type']) {
					$dbchat->update($chat);
				}
			} else{
				$this->db->chat->insert($chat);
			}
		} else return $chat;
	}
	function saveUsers($users) {
		if(is_array($users) && count($users)>0 ) {
			$available=array('id','first_name','username','last_name');
			foreach ($users as $key => $value) {
				if(!isset($value['last_name'])) $users[$key]['last_name']='';
				if(!isset($value['username'])) $users[$key]['username']='';

				foreach ($value as $k => $v) {
					if(!in_array($k,$available)) {
						$this->send(OWNER,'new user key: '.$k.' = '.$v);
						unset($users[$key][$k]);
					}
				}
			}

			$dbusers=$this->db->user->where(array('id'=>array_keys($users)));
			foreach ($dbusers as $key => $value) {
				$update=array();
				if((string)@$users[$key]['first_name']!=$value['first_name']) {
					$update['first_name']=$users[$key]['first_name'];
				}
				if((string)@$users[$key]['last_name']!=$value['last_name']) {
					$update['last_name']=$users[$key]['last_name'];
				}
				if((string)@$users[$key]['username']!=$value['username']) {
					$update['username']=$users[$key]['username'];
				}
				if(count($update)>0) {
					$value->update($update);
				}
				unset($users[$key]);
			}

			if(count($users)>0) {
				$this->db->user->insert_multi($users);
			}
		}
	}
	function save($mes) {
		$a=array(
			'origin_array'=>(string)$mes,
			'bot_id'=>$this->botid
			);
		// print_r($a);
		if($mes->update_id()) $a['update_id']=$mes->update_id();
		if($mes->message_id()) $a['message_id']=$mes->message_id();
		if($mes->getFromID()) $a['user_id']=$mes->getFromID();
		if($mes->getChatID()) $a['chat_id']=$mes->getChatID();
		if($mes->getDate()) $a['date']=$mes->getDate();
		if($mes->getText()) $a['text']=$mes->getText();
		if($mes->getFileArray()) $a['photo_array']=json_encode($mes->getFileArray());
		if($mes->getMediaType()) $a['messagetype']=$mes->getMediaType();
		// print_r($a);
		$insert=$this->db->messages->insert($a);
		// print_r($insert);
		$this->saveUsers($mes->getAllUsers());
		$this->saveChats($mes->getChat());

	}
	function setWebHook($url,$cert=false) {
		if(strlen($url)<13) {
			return false;
		}
		$multipart=array();
		$multipart[]=array(
			'name'=>'url',
			'contents'=>$url
			);
		if($cert!==false) {
			if(is_file($cert)) {
				$multipart[]=array(
					'name'=>'certificate',
					'contents'=>fopen($cert, 'r'),
					);
			} else {
				$multipart[]=array(
					'name'=>'certificate',
					'contents'=>$cert,
					);
			}
		}
		$a=$this->Gc->post('setWebhook',['multipart' => $multipart]);
		$code = $a->getStatusCode();
		if($code>=200 && $code<500) {
			$body=$a->getBody();
			$ans=new tgBotMessage($body);
			if($this->log2db && $this->db) {
				$this->save($ans);
			}
			return $ans;
		}
	}
	function getInlineUser($user) {
		if(!isset($user['last_name'])) $user['last_name']='';
		if(!isset($user['username'])) $user['username']='';
		return trim($this->removeLineBreak($user['first_name']).' '.$this->removeLineBreak($user['last_name'])).(strlen(@$user['username'])>0?' (@'.$user['username'].')':'');
	}
	function removeLineBreak($string) {
		return preg_replace('/ +/',' ',str_replace(array("\n","\t",),' ',$string));
	}
	function clearHTML($string) {
		return str_replace(array('&','<','>'), array('&amp;','&lt;','&gt;'), $string);
	}

}

class tgBotMessage {
	private $data;
	private $json;

	function __construct($json) {
		$this->json=(string)$json;
		$this->data=json_decode($json,true);
	}
	function __toString() {
		return $this->json;
	}
	function getChat() {
		$m=$this->getMessage();
		$out=false;
		if($m) {
			if(isset($m['chat'])) {
				$out=$m['chat'];
			}
		
			if($out['type']=='private') {
				if(!isset($out['last_name'])) $out['last_name']='';
				if(!isset($out['username'])) $out['username']='';
				$out['title']=trim($out['first_name'].' '.$out['last_name']).(strlen(@$out['username'])>0?' (@'.$out['username'].')':'');
				unset($out['first_name']);
				unset($out['last_name']);
				unset($out['username']);
			}
		}
		return $out;
	}
	function getAllUsers($byType=false) {
		$out=array();
		$m=$this->getMessage();
		if($m) {
			$a=array('forward_from','from','new_chat_member','left_chat_member');
			foreach ($a as $t) {
				if(isset($m[$t])) $out[$byType?$t:$m[$t]['id']]=$m[$t];
			}
			if(isset($m['pinned_message'])) {
				foreach ($a as $t) {
					if(isset($m['pinned_message'][$t])) $out[$byType?$t:$m['pinned_message'][$t]['id']]=$m['pinned_message'][$t];
				}				
			}
			if(isset($m['reply_to_message'])) {
				foreach ($a as $t) {
					if(isset($m['reply_to_message'][$t])) $out[$byType?$t:$m['reply_to_message'][$t]['id']]=$m['reply_to_message'][$t];
				}				
			}
			
		}
		if(isset($this->data['callback_query']['from'])) {
			$out[$this->data['callback_query']['from']['id']]=$this->data['callback_query']['from'];
		}
		if(isset($this->data['result']['first_name'])) {
			$out[$this->data['result']['id']]=$this->data['result'];
		}

		if(count($out)>0) {
			return $out;
		} else {
			return false;
		}

	}
	function update_id() {
		if(isset($this->data['update_id'])) {
			return $this->data['update_id'];
		} else return false;
	}
	function message_id() {
		$m=$this->getMessage();
		if(isset($m['message_id'])) {
			return $m['message_id'];
		} else return false;
	}
	function getFromID() {
		$m=$this->getMessage();
		if(isset($m['from']['id'])) {
			return $m['from']['id'];
		} else return false;
	}
	function getChatID() {
		$m=$this->getMessage();
		if(isset($m['chat']['id'])) {
			return $m['chat']['id'];
		} else return false;
	}
	function getDate() {
		$m=$this->getMessage();
		if(isset($m['date'])) {
			return $m['date'];
		} else return false;
	}
	function getFileArray() {
		$a=array('audio','document','photo','sticker','video','voice','contact','location','venue','new_chat_photo');
		$m=$this->getMessage();
		foreach ($a as $type) {
			if(isset($m[$type])) {
				return $m[$type];
			}
		}
		return false;
	}
	function getMediaType() {
		$posible=array('forward_from','forward_from_chat','reply_to_message','text','audio','document','game','photo','sticker','video','voice','contact','location','venue','new_chat_member','left_chat_member','new_chat_title','new_chat_photo','group_chat_created','supergroup_chat_created','channel_chat_created','migrate_to_chat_id','migrate_from_chat_id','pinned_message');
		$out=array();
		$m=$this->getMessage();
		foreach ($m as $key => $value) {
			if(in_array($key, $posible)) {
				$out[]=$key;
			}
		}
		sort($out);
		return implode(',',$out);

	}
	function getMessage() {
		$m=false;
		if(isset($this->data['message'])) {
			$m=$this->data['message'];
		} elseif(isset($this->data['result']['message_id'])) {
			$m=$this->data['result'];
		} elseif(isset($this->data['edited_message'])) {
			$m=$this->data['edited_message'];
		} elseif(isset($this->data['callback_query']['message'])) {
			$m=$this->data['callback_query']['message'];
		}

		return $m;
	}
	function getChannelID() {
		$m=$this->getMessage();
		if(isset($m['chat']['id'])) {
			return $m['chat']['id'];
		} else {
			return false;
		}
	}
	function getUserID() {
		$m=$this->getMessage();
		if(isset($m['from']['id'])) {
			return $m['from']['id'];
		} else {
			return false;
		}
	}
	function error() {
		if(!isset($this->data['ok']) || $this->data['ok']) {
			return false;
		} elseif(isset($this->data['description']) && isset($this->data['error_code'])) {
			return 'Code: '.$this->data['error_code'].' / '.$this->data['description'];
		} else {
			return 'Error Unknown';
		}
	}
	function getText() {
		$m=$this->getMessage();
		if(isset($m['text'])) {
			return $m['text'];
		}elseif(isset($m['caption'])) {
			return $m['caption'];
		} else return false;
	}
	function getEntities() {
		$m=$this->getMessage();
		if(isset($m['entities'])) {
			return $m['entities'];
		} else {
			return false;
		}
	}
	function is($key) {
		if(in_array($key, array('callback_query','message','inline_query','chosen_inline_result','edited_message')) && isset($this->data[$key])) {
			return true;
		}
		if($key==='edited' && isset($this->data['edited_message'])) {
			return true;
		}
		$m=$this->getMessage();

		if(in_array($key, array('forward')) && isset($m['forward_from'])) {
			return true;
		}
		if(in_array($key, array('pinned')) && isset($m['pinned_message'])) {
			return true;
		}
/*
		if(in_array($key, array('new_chat_member')) && isset($m['new_chat_participant'])) {
			return true;
		}
		if(in_array($key, array('left_chat_member')) && isset($m['left_chat_participant'])) {
			return true;
		}
*/
		if(isset($m[$key])) {
			return true;
		}

		return false;
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