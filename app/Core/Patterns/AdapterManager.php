<?php 

namespace App\Core\Patterns;


abstract class AdapterManager{

    abstract protected function getKey(): string;
    public function __call($method, $args){
        if(!method_exists($this, $method)){
            if (!config("adapters.{$this->getKey()}.contexts.{$method}")) {
                $defaultAdapter = config("adapters.{$this->getKey()}.default");
                $instance = $this->use($defaultAdapter);
                if(!method_exists($instance, $method)){
                    throw new \InvalidArgumentException("Message adapter [{$defaultAdapter}] does not have method [{$method}].");
                }
                return call_user_func_array([$instance, $method], $args);
            }
            return $this->use($method);
        }else{
            return call_user_func_array([$this, $method], $args);
        }
    }
    protected function config($adapter){
        return config("adapters.{$this->getKey()}.contexts.{$adapter}");
    }
    protected function getContext($adapter){
        $config = $this->config($adapter);
        if($config){
            return $config['context'];
        }
        return null;
    }
    public function use(string $adapter){
        
        if (!config("adapters.{$this->getKey()}.contexts.{$adapter}")) {
            throw new \InvalidArgumentException("Message adapter [{$adapter}] is not defined.");
        }

        return app()->make($this->getContext($adapter),['config'=>$this->config($adapter)]);
    }
}