<?php

namespace Tys\Controllers\Middleware\ModelMiddleware;

use \Tys\Controllers\Middleware\ModelMiddleware\InvalidParamsArrayException;

/**
 * Params validation class for ModelMiddleware params
 *
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
class ModelMiddlewareParams extends \ArrayObject
{   
    private function validateType()
    {
        if (!isset ($this['type'])) {
            throw new InvalidParamsArrayException('No type set', InvalidParamsArrayException::NO_TYPE);
        }
        if (!in_array($this['type'], ['callable', 'object', 'service'])) {
            throw new InvalidParamsArrayException(
                'Invalid type: ' . $this['type'],
                InvalidParamsArrayException::INVALID_TYPE
            );
        }
        if (!isset ($this['call'])) {
            throw new InvalidParamsArrayException(
                'Callable not set',
                InvalidParamsArrayException::MISSING_CALL
            );
        }
        if ($this['type'] != 'callable') {
            if (!isset ($this['name'])) {
                throw new InvalidParamsArrayException(
                    'Missing object name',
                    InvalidParamsArrayException::MISSING_OBJECT_NAME
                );
            }
            
        }
    }
    
    public function __construct(array $params)
    {
        parent::__construct($params);
        $this->validateType();
    }
    
}
