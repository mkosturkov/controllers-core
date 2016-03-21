<?php

namespace Tys\Controllers\Middleware\ModelMiddleware;

use \Tys\Controllers\Controller;
use \Tys\Controllers\Contracts\MiddlewareInterface;

/**
 * A middleware to wrap arround callbacks.
 *
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
class ModelMiddleware implements MiddlewareInterface
{
    /**
     * Params passed to the constructor
     * 
     * @var ModelMiddlewareParams
     */
    private $params;
    
    private function makeCallable(Controller $controller)
    {
        if ($this->params['type']== 'callable') {
            return $this->params['call'];
        }
        if ($this->params['type'] == 'object') {
            $className = $this->params['name'];
            $object = new $className();
        } else if ($this->params['type'] == 'service') {
            $object = $controller->getDIC()->get($this->params['name']);
        }
        return [$object, $this->params['call']];
    }
    
    public function __construct(ModelMiddlewareParams $params)
    {
        $this->params = $params;
    }

    public function run(Controller $controller)
    {
        $callable = $this->makeCallable($controller);
        return $callable();
    }
}
