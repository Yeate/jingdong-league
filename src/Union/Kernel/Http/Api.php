<?php



namespace JingDongLeague\Union\Kernel\Http;


use JingDongLeague\Exception\UnionException;
use JingDongLeague\Kernel\Http;

class Api extends UnionApiIterator
{
    const URL = 'https://router.jd.com/api';
    private $param = [
        'http'=>'',
        'appKey'=>'',
        'appSecret'=>'',
        'timestamp'=>'',
        'v'=>'',
        'method'=>'',
        'signMethod'=>'',
        'requestParams'=>'',
    ];
    private $status;
    private $hasNext=false;
    
    
    
    public function __construct($appKey, $appSecret)
    {
        $this->param['appKey'] = $appKey;
        $this->param['appSecret'] = $appSecret;
        $this->param['timestamp'] = date('Y-m-d H:i:s', time());
        $this->param['v']='1.0';
        $this->param['signMethod'] = 'md5';
        if(!$this->param['http']){
            $this->param['http'] = new Http();
        }
    }
    
    public function makeParams():array {
        $systemParameter = array(
            'app_key' => $this->param['appKey'],
            'format' => 'json',
            'v' => $this->param['v'],
            'timestamp' => $this->param['timestamp'],
            'method' => $this->param['method'],
            'sign_method' => $this->param['signMethod'],
            'param_json' => json_encode($this->param['requestParams'])
        );
        $sign = $this->getStringToSign($systemParameter);
        $parameter = array_merge($systemParameter, ['sign' => $sign]);
        ksort($parameter);
        return $parameter;
    }
    
    /**
     * 生成签名
     * @param $parameter
     * @return string
     */
    protected function getStringToSign($parameter)
    {
        ksort($parameter);
        $str = '';
        foreach ($parameter as $key => $value) {
            if (!empty($value)) {
                $str .= ($key) . ($value);
            }
        }
        
        $str = $this->param['appSecret'] . $str . $this->param['appSecret'];
        
        $signature = strtoupper(md5($str));
        
        return $signature;
    }
    
    public function request($method,$requestParams=[]){
        $this->param['method'] = $method;
        $this->param['requestParams'] = $requestParams;
        $data = $this->makeParams();
        $response = call_user_func_array([$this->param['http'],'post'],[self::URL,$data]);
        $this->status = $response->status;
        $content = $response->content;
        if(isset($content['errorResponse'])){
            throw new UnionException(json_encode($content));
        }
        array_walk_recursive ($content,function($value,$key){
    
            if($key=='result'){
    
                $result = json_decode($value,true);
                $this->items = isset($result) && isset($result['data'])?$result['data']:[];
                $this->hasNext = isset($result) && isset($result['hasMore'])?$result['hasMore']:false;
    
                if($result['code']!=200) throw new UnionException($result['message']);
            }
        });
        return $this;
        
    }
    
    public function toArray(){
        return $this->items;
    }
    public  function isEmpty(){
        if($this->items){
            return false;
        }
        return true;
    
    }
    
    public function __get($name)
    {
        if(isset($this->items[$name])) return $this->items[$name];
        throw new \ErrorException(sprintf('Undefined property: %s::$%s',__CLASS__,$name));
    }
    
    
}