<?php

class ArrayCacheStorage
{
    protected $data = [];

    public function get($name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        return null;
    }

    public function delete($name)
    {
        if (isset($this->data[$name])) {
            unset($this->data[$name]);
        }
    }

    protected function arrayDataRegExp($string)
    {
        $keys = array_keys($this->data);
        $regExp = "/".str_replace("*", ".*", $string)."/";
        return preg_grep($regExp, $keys);
    }

    public function clear($string = null)
    {
        if (null === $string) {
            $this->data = [];
            return $this->data;
        }

        $arrayDataMatch = $this->arrayDataRegExp($string);

        foreach ($arrayDataMatch as $dataMatch) {
            $this->delete($dataMatch);
        }

        return $this->data;
    }

    public function save($name, $data)
    {
        return $this->set($name, $data);
    }

    public function set($name, $data)
    {
        if (!defined("NO_CACHE_OBJECTS")) {
            return $this->data[$name] = $data;
        }
    }
}
