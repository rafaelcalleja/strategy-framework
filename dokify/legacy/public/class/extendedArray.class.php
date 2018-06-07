<?php
class extendedArray extends ArrayObject
{
    public function remove($key, $strict = false)
    {
        $array = $this->getArrayCopy();

        if (null === $key = array_search($key, $array, $strict)) {
            return false;
        }

        unset($array[$key]);

        $class = get_class($this);

        return new $class($array);
    }

    public function prepend($item)
    {
        $class = get_class($this);
        $array = $this->getArrayCopy();
        array_unshift($array, $item);
        return new $class($array);
    }

    public function shift()
    {
        $class = get_class($this);
        $array = $this->getArrayCopy();
        array_shift($array);
        return new $class($array);
    }

    public function map ()
    {
        $map    = [];
        $args   = func_get_args();
        $fn     = array_shift($args);

        foreach ($this as $item) {
            $params = array_merge([$item], $args);
            $map[]  = call_user_func_array($fn, $params);
        }

        return $map;
    }

    public function keys()
    {
        return array_keys($this->getArrayCopy());
    }

    public function filter($fn = null)
    {
        return new static(array_filter($this->getArrayCopy(), $fn));
    }

    public function merge($arr)
    {
        $class = get_class($this);
        $arr = ( $arr instanceof ArrayObject ) ? $arr->getArrayCopy() : $arr;
        if( !is_array($arr) ) $arr = array($arr);
        return new $class( array_merge($this->getArrayCopy(), $arr) );
    }

    public function mergeRecursive($arr)
    {
        if ($arr instanceof ArrayObject) $arr = $arr->getArrayCopy();
        $result = array_merge_recursive($this->getArrayCopy(), $arr);
        return new self($result);
    }

    public function mergeWithKeys($arr)
    {
        $class = get_class($this);
        $result = $this->getArrayCopy();

        foreach($arr as $key => $value) {
            $result[$key] = $value;
        }

        return new self($result);
    }

    public function unique()
    {
        $class = get_class($this);
        return new $class( array_unique($this->getArrayCopy()) );
    }

    public function slice($len=1, $offset=0)
    {
        $class = get_class($this);
        return new $class( array_slice($this->getArrayCopy(), $offset, $len) );
    }

    /** comprobar si el valor se encuentra en la lista **/
    public function contains($param)
    {
        return in_array($param, $this->getArrayCopy());
    }

    public function match($param)
    {
        $param = $param instanceof ArrayObject ? $param->getArrayCopy() : $param;

        // Si es un array
        if( is_array($param) ){
            while($test = array_shift($param)){
                if( $this->contains($test) ) return true;
            }

            return false;
        }

        return $this->contains($param);
    }

    public function getFirst()
    {
        return $this->get(0);
    }

    public function getLast()
    {
        $arr = $this->getArrayCopy();
        return end($arr);
    }

    public function get($index)
    {
        return (isset($this[$index])) ? $this[$index] : false;
    }

    public function __toString()
    {
        return implode(",", array_map("strtolower", $this->getArrayCopy()));
    }

    public function diff($arr)
    {
        $class = get_class($this);
        $arr = ( $arr instanceof ArrayObject ) ? $arr->getArrayCopy() : $arr;
        if( !is_array($arr) ) $arr = array($arr);
        return new $class( array_diff($this->getArrayCopy(), $arr) );
    }

    public function add($element)
    {
        $class = get_class($this);
        $copy = $this->getArrayCopy();
        array_push($copy, $element);
        return new $class( $copy );
    }

    public function reverse()
    {
        $class = get_class($this);
        return new $class(array_reverse($this->getArrayCopy()));
    }

    // See http://stackoverflow.com/questions/19010180/array-intersect-throws-errors-when-arrays-have-sub-arrays/19013342#19013342
    public function intersect($arr)
    {
        $class  = get_class($this);
        $arr    = ( $arr instanceof ArrayObject ) ? $arr->getArrayCopy() : $arr;

        if (!is_array($arr)) {
            $arr = array($arr);
        }

        $result = array_map("unserialize", array_intersect(self::serializeArrayValues($this->getArrayCopy()), self::serializeArrayValues($arr)));

        return new $class( $result );
    }

    private static function serializeArrayValues($arr)
    {
        foreach ($arr as $key => $val) {
            //In order to maintain index association it is better to use `asort` instead of `sort` built-in function
            asort($val);
            $arr[$key]=serialize($val);
        }

        return $arr;
    }
}
