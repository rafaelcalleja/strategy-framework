<?php

class empresa
{
    protected $name;

    protected $id;

    public static function makeFromData(array $data): self
    {
        $company = new self();
        $company->name = $data['name'];
        $company->id = rand();
    }

    /**
     * @return mixed
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function id()
    {
        return $this->id;
    }


}