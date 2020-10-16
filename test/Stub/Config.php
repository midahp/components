<?php

class Components_Stub_Config extends Horde\Components\Config\Base
{
    public function __construct($arguments, $options)
    {
        $this->_arguments = $arguments;
        $this->_options = $options;
    }
}