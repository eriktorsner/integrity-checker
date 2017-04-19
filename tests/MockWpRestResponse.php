<?php

class WP_REST_Response
{
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function header($name, $value)
    {

    }
}