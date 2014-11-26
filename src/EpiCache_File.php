<?php
    class EpiCache_File extends EpiCache
    {
        private $expiry   = null;
        private $path     = null;


        public function __construct($params = array())
        {
            if(empty($params[0])){
                return null;
            }
            $this->path     = $params[0];
            $this->expiry   = $params[1] !== ''  ? $params[1] : 0;

            if(!is_dir($this->path) || !is_writable($this->path)){
                EpiException::raise(new EpiCacheFilePathNotWriteableException('Path '. $this->path . ' does not exist, or is not writeable'));
            }
        }

        public function delete($key)
        {
            if(empty($key)){
                return null;
            }
            $this->file_delete($key);
        }

        public function get($key)
        {
            if(empty($key)){
                return null;
            }else if($getEpiCache = $this->getEpiCache($key)){
                return $getEpiCache;
            }else{
                $value = $this->file_get($key);
                $this->setEpiCache($key, $value);
                return $value;
            }
        }

        public function set($key = null, $value = null, $expiry = null)
        {
            if(empty($expiry) && $expiry !== 0) {
                $expiry = $this->expiry;
            }
            if(empty($key) || $value === null){
                return false;
            }

            $this->file_set($key, $value, $expiry);
            $this->setEpiCache($key, $value);
            return true;
        }

        private function encode($value,$expiry){
            return var_export(array(
                "added"=>@date("U"),
                "value"=>$value,
                "expiry"=>$expiry
            ),true);
        }

        private function file_set($key, $value, $expiry){
            $filename = $this->file_name($key);
            $content = '<?php' . PHP_EOL . 'return ' . $this->encode($value,$expiry) . ';';
            return is_numeric(file_put_contents($filename,$content));
        }

        private function file_get($key){
            $filename = $this->file_name($key);
            if(!file_exists($filename)){
                return null;
            }

            $data = include($filename);

            if(!is_array($data) || !isset($data['added']) || !isset($data['expiry']) || !isset($data['value'])) {
                return null;
            }

            if($data['expiry'] !== 0 && $data['added'] + $data['expiry'] < @date("U")) {
                // exp
                @unlink($filename);
                return null;
            }

            return $data['value'];

        }

        private function file_delete($key){
            $filename = $this->file_name($key);
            return @unlink($filename);
        }

        private function file_name($key){
            $safe_key = strtolower(preg_replace("/[^a-zA-Z0-9_\s\.]+/","",$key));
            return $this->path . DIRECTORY_SEPARATOR . '.' . $safe_key . '.cache';
        }
    }