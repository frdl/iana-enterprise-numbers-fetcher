<?php

namespace Frdlweb;
/*
Fetch List of IANA Enterprise Numbers from IANA
Download: https://github.com/frdl/iana-enterprise-numbers-fetcher
----------------------------------------------------------------------------
MIT License

Copyright (c) 2023 Till Wehowski

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
----------------------------------------------------------------------------
Example:
header('Content-Type: text/plain');
$Fetcher = new IanaPenListFetcher();
$result = $Fetcher();
 echo print_r(count($result), true). " Records found\n";
 echo print_r($Fetcher('till@webfan.de'), true). "\n";
 echo print_r($Fetcher(37553), true). "\n";
 echo print_r($Fetcher('1.3.6.1.4.1.37553'), true). "\n";
 echo print_r($Fetcher('Wehowski'), true). "\n";
 echo print_r($Fetcher('frdl'), true). "\n";
*/

class IanaPenListFetcher
{
	protected $url;
	protected $cachefile;
	protected $cachefileResult;
	protected $list = false;
	protected $cachelimit;
	protected $root = '1.3.6.1.4.1';
	
	public function __construct(){
		$this->setRoot('1.3.6.1.4.1');
		$this->setUrl('https://www.iana.org/assignments/enterprise-numbers/enterprise-numbers');
		$this->setCachelimit(60 * 60);
		$this->setCachefile(__DIR__.\DIRECTORY_SEPARATOR.'penlist.php');
	}
	public function setRoot(string $root){
		$this->root=$root;
	}	
	public function setUrl(string $url){
		$this->url=$url;
	}	
	public function setCachefile(string $cachefile){
		if('.php' !== substr($cachefile, -4)){
		  $cachefile.='.php';	
		}
		$this->cachefile=$cachefile;
		$this->cachefileResult=$this->cachefile.'.result.php';
	}	
	public function setCachelimit(int $cachelimit){
		$this->cachelimit=$cachelimit;
	}
	
	public function __invoke()   {
		return \call_user_func_array([$this,'get'], func_get_args());
	}	
	public function get(int | string $query = null) : array | bool {
		if(!is_array($this->list)){
			$this->_buildList();
		}
		if(!is_null($query)){					
			    if(is_int($query) || is_numeric($query)){	
					$query= intval($query);		
					$type = 'id';
				}elseif (\filter_var(str_replace('&', '@', $query), \FILTER_VALIDATE_EMAIL)) {			
					$type = 'email';	
				}elseif(preg_match("/^([\d\.]+)$/", $query)){			
					$type = 'oid';			
				}else{			
					$type = 'name';			
				}		
			
			$columns = array_column($this->list, $type);
			$found_key = array_search($query, $columns);
			
				if(false === $found_key && 'id' !== $type){
				   foreach ($this->list as $index => $item){     
					   if (stripos($item['name'], $query) !== false || stripos($item['org'], $query) !== false ){        
						   return $item;  
					   }  
				   }
				   foreach ($this->list as $index => $item){     
					   if (stripos($item['email'], $query) !== false ){        
						   return $item;  
					   }  
				   }							
				}
			
			if(false === $found_key){
			  return false;	
			}else{
			  return $this->list[$found_key];	
			}
		}//$query
		return $this->list;
	}		
	
	protected function _buildList(){
        if(!file_exists($this->cachefileResult) || filemtime($this->cachefileResult) < time() - $this->cachelimit){
			$lines = $this->_getFileLines();	
			$found = false; 
			$result = []; 
			while(   false===$found  ){  		 
				$line = array_shift($lines);	 
				if(is_numeric(trim($line))){		
					$found = true; 		
					array_unshift($lines, $line);		
					break;	
				} 
			}
			$current = false; 
			foreach ($lines as $line_num => $line) {	
				$l = trim($line);	 	
				if(is_numeric($l)){			 
					$current = [];		
					$current['id'] = intval($l);		
				}elseif (\filter_var(str_replace('&', '@', $l), \FILTER_VALIDATE_EMAIL)) {			
					$current['email'] = str_replace('&', '@', $l);		
				}elseif(!isset($current['org'])){			
					$current['org'] = $l;		
				}else{			
					$current['name'] = $l;		
				}
	
				if(count($current) >=  4){		
					$current['oid'] = $this->root.'.'.$current['id'];
					$result[] = $current;       
					$current = false;	
				} 
			}		
			
			$exp = var_export($result, true);
			$phpCode = <<<PHPCODE
<?php
 return $exp;
PHPCODE;			
			  file_put_contents($this->cachefileResult, $phpCode);	
			$this->list=$result;
		}//!cachefile
		else{
			$this->list= require $this->cachefileResult;
		}
		
		return $this->list;
	}
	
	protected function _getFileLines(){
      if(!file_exists($this->cachefile) || filemtime($this->cachefile) < time() - $this->cachelimit){

        $options = [
        'http' => [
			 
	        'timeout' => 180,  
	       'follow_location' => true,	
           'method'  => 'GET',
            'ignore_errors' => true,
            'header'=> "User-Agent: Frdlweb WEID API Client\r\n"
               // . "Content-Length: " . strlen($data) . "\r\n"
				,
           ]
         ];
          $context  = stream_context_create($options);
          $code = file_get_contents($this->url, false, $context);
		  if(false===$code){
			throw new \Exception('Could not fetch '.$this->url);  
		  }
          file_put_contents($this->cachefile, $code);	
       }		
		return file($this->cachefile, \FILE_SKIP_EMPTY_LINES);
	}
	
	
} 
