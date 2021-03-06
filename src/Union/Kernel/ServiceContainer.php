<?php



namespace JingDongLeague\Union\Kernel;


use Pimple\Container;


class ServiceContainer extends Container
{

    /**
     * @var array
     */
    protected $providers = [];
    
    protected $default_config = [
        'appKey'=>'',
        'appSecret'=>'',
        'timestamp'=>'',
        'v'=>'1.0',
        'signMethod'=>'md5',
    ];
    /**
     * @var array
     */
    protected $userConfig = [];

    /**
     * Constructor.
     *
     * @param array       $config
     * @param array       $prepends
     * @param string|null $id
     */
    public function __construct(array $config = [], array $prepends = [])
    {
        $this->registerProviders($this->getProviders());
        parent::__construct($prepends);

        $this->userConfig = array_merge($this->default_config,$config);


    }
    
    
    public function __get($id)
    {
        if(isset($this->userConfig[$id])){
            return $this->userConfig[$id];
        }else{
            return $this->offsetGet($id);
        }
        
    }
    
    /**
     * Magic set access.
     *
     * @param string $id
     * @param mixed  $value
     */
    public function __set($id, $value)
    {
        $this->offsetSet($id, $value);
    }
   

   
    public function getProviders()
    {
        return $this->providers;
    }
    
    public function config(){
        return $this->userConfig;
    }

    /**
     * @param array $providers
     */
    public function registerProviders(array $providers)
    {
        foreach ($providers as $provider) {
            parent::register(new $provider());
        }
    }
}
