<?php

class usuario
{
    protected $name;

    protected $company;

    protected $id;

    public static function makeFromData(array $data): self
    {
        $user = new self();
        $user->name = $data['name'];
        $user->company = \empresa::makeFromData($data['company']);
        $user->id = rand();
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

    public function getCompany(): \empresa
    {
        return $this->company;
    }
}