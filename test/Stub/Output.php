<?php

class Components_Stub_Output extends Horde\Components\Output
{
    /**
     * Constructor.
     *
     * @param Horde_Cli         $cli    The CLI handler.
     * @param Components_Config $config The configuration for the current job.
     */
    public function __construct($options = array())
    {
        $this->output = new Components_Stub_Output_Cli();

        parent::__construct(
            $this->output,
            $options
        );
    }

    public function getOutput()
    {
        return $this->output->messages;
    }
}

class Components_Stub_Output_Cli
{
    public $messages = array();

    public function message($message, $type)
    {
        $this->messages[] = $message;
    }

    public function fatal($text)
    {
        throw new Components_Exception($text);
    }
}

