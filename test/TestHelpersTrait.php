<?php

/**
 * A trait to hold some testing helpers
 *
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
trait TestHelpersTrait
{

    /**
     * Invoke an unaccessible (private, protected) method on a class
     * and return the its return value.
     * 
     * @param object $object The object to invoke the method on
     * @param string $methodName The name of the method to invoke
     * @param mixed $args... unlimited OPTIONAL The arguments to pass to the 
     */
    public function invokeUnaccessableMethod($object, $methodName, ...$args)
    {
        $reflectionClass = new ReflectionClass(get_class($object));
        $reflectionMethod = $reflectionClass->getMethod($methodName);
        $reflectionMethod->setAccessible(true);
        return $reflectionMethod->invokeArgs($object, $args);
    }
    
    /**
     * Get the value of an unaccessable (private, protected) property of an object
     * 
     * @param object $object The object to get the property value on
     * @param string $propertyName The name of the property to get
     */
    public function getUnaccessablePropertyValue($object, $propertyName)
    {
        $reflectionClass = new ReflectionClass(get_class($object));
        $reflectionProperty = $reflectionClass->getProperty($propertyName);
        $reflectionProperty->setAccessible(true);
        return $reflectionProperty->getValue($object);
    }

}
