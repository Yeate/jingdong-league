<?php



namespace JingDongLeague\Union\Kernel\Http;


use JingDongLeague\Exception\UnionException;

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
    private $total;
    private $hasNext=false;
    
    
    public function total(){
        return $this->total;
    }
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
        if(isset($content['error_response']) || isset($content['errorResponse'])){
            throw new UnionException(json_encode($content));
        }
        array_walk_recursive ($content,function($value,$key)use($requestParams){
            if($key=='result'){
                $result = json_decode($value,true);
                $this->total = isset($result) && isset($result['totalCount'])?$result['totalCount']:1;
                $this->items = isset($result) && isset($result['data'])?$result['data']:[];
                if(isset($result) && isset($result['hasMore'])){
                    $this->hasNext = $result['hasMore'];
                }else{
                      $param = current($requestParams);
                      if(isset($param['pageIndex']) && isset($param['pageSize'])){
                          $this->hasNext=$this->total>($param['pageIndex']*$param['pageSize'])?true:false;
                      }elseif(isset($param['pageSize'])){
                          $this->hasNext=count($this->items)==$param['pageSize']?true:false;
                      }else{
                          $this->hasNext=false;
                      }
                }

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

    public  function __call($name, $arguments)
    {
        if(!property_exists($this,$name)){
            throw new \Exception(sprintf("Call to undefined method JingDongLeague\Union\Kernel\Http\Api::%s()",$name));
        }else{
            return $this->$name;
        }
    }

    public function __get($name)
    {
        if(isset($this->items[$name])) return $this->items[$name];
        return $this->$name;
        throw new \ErrorException(sprintf('Undefined property: %s::$%s',__CLASS__,$name));
    }
    
    
}
