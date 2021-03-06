<?php



namespace JingDongLeague\OpenAuthPlatForm\Kernel;


use Pimple\Container;


class ServiceContainer extends Container
{

    /**
     * @var array
     */
    protected $providers = [];
    

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

        $this->userConfig = array_merge(config('jingdong.oauth_public_param'),$config);
        $this->userConfig = array_merge(config('jingdong.oauth_public_param'),$config);


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
    {dd($id,$value);
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
