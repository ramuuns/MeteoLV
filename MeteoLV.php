<?php
/**
 * Copyright (C) 2013 Ramūns Usovs
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of 
 * this software and associated documentation files (the "Software"), to deal in the 
 * Software without restriction, including without limitation the rights to use, copy, 
 * modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, 
 * and to permit persons to whom the Software is furnished to do so, subject to the 
 * following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all copies 
 * or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, 
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR 
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE 
 * FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR 
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER 
 * DEALINGS IN THE SOFTWARE.
 * 
 */

	class MeteoLV {
		
		protected $config = array(
			 'url' => "http://www.meteo.lv/meteorologijas-operativie-dati/"
		);
		
		static $paramIds = array(
			 "121"=>"temperature"
			,"117"=>"wind_direction"
			,"113"=>"wind_speed"
			,"114"=>"humidity"
			,"118"=>"pressure"
			,"119"=>"rainfall"
			,"120"=>"snow_cover"
			,"116"=>"visibility"
		);
		
		protected $data;
		
		public function __construct(array $config = array()) {
			$this->config['cache_dir'] = dirname(__FILE__);
			if ( is_array($config) ) {
				$this->config = $config + $this->config;
			}
		}
		
		public function loadData() {
			$cache_file = $this->config['cache_dir']."/meteocache";
			if ( file_exists($cache_file) && date("H",filemtime($cache_file)) == date("H") ) {
				$this->data = unserialize(file_get_contents($cache_file));
			} else if (file_exists($cache_file) ) {
				$this->data = unserialize(file_get_contents($cache_file));
				$this->downloadData($cache_file);
			} else {
				$this->downloadData($cache_file);
			}
		}
		
		protected function downloadData($cache_file) {
			
			$url = $this->config['url']."?date=&time=&parameterId=&fullMap=0&rnd=".time();
			
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.835.202 Safari/535.1");
			$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
			$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
			$header[] = "Cache-Control: max-age=0";
			$header[] = "Connection: keep-alive";
			$header[] = "Keep-Alive: 300";
			$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
			$header[] = "Accept-Language: en-us,en;q=0.5";
			$header[] = "Pragma: ";
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			
			$cookie_file = $this->config['cache_dir']."/cookiefile.txt";
			curl_setopt($ch, CURLOPT_COOKIESESSION, true);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
			$resp = curl_exec($ch);
			$header_size = curl_getinfo($ch,CURLINFO_HEADER_SIZE);
			$json = substr($resp, $header_size);
			
			$data = json_decode($json, true);
			
			foreach ( $data['stations'] as $k => $station ) {
				if ( !isset($this->data[$station['name']]) ) {
					//fill in station data
					$this->data[$station['id']] = array(
						 'id' => $station['id']
						,'name' => $station['name']
						,'lat' => $station['latitude']
						,'long' => $station['longitude']
						,'last_update' => date("Y-m-d H:i",  strtotime($data['date']." ".$data['time']))
						,'params' => array()
					);
				}
				if ( count($station['parameters']) ) {
					$this->data[$station['name']]['last_update'] = date("Y-m-d H:i",  strtotime($data['date']." ".$data['time']));
					foreach ( $station['parameters'] as $param ) {
						if ( $param['value'] == "" ) {
							continue;
						}
						$this->data[$station['name']]['params'][self::$paramIds[$param['parameterId']]] = trim($param['value']);
						if ( self::$paramIds[$param['parameterId']] == "wind_speed" ) {
							$this->data[$station['name']]['params'][self::$paramIds[$param['parameterId']]] = str_replace("/", " - ", $this->data[$station['name']]['params'][self::$paramIds[$param['parameterId']]]);
						}
						//make the following floats
						if ( self::$paramIds[$param['parameterId']] == "temperature" ||
							 self::$paramIds[$param['parameterId']] == "pressure" ) {
							$d = $this->data[$station['name']]['params'][self::$paramIds[$param['parameterId']]];
							$d = str_replace(",",".",$d);
							$d = (float)$d;
							$this->data[$station['name']]['params'][self::$paramIds[$param['parameterId']]] = $d;
						}
					}
				}
			}
			
			file_put_contents($cache_file, serialize($this->data));
		}
		
		public function getData($station = "") {
			if ( $station == "" ) {
				return $this->data;
			}
			if ( array_key_exists($station, $this->data) ) {
				return $this->data[$station];
			}
			return null;
		}
	}