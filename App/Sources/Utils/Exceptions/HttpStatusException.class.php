<?php
    class HttpStatusException extends Exception
    {
        public $StatusCode;
        
        public $Name;
        
        public $ErrorCode;
        
        public function __construct(integer $statusCode, string $name, integer $errorCode, string $message)
        {
            parent::__construct($message);
            
            $this->StatusCode = $statusCode;
            $this->Name = $name;
            $this->ErrorCode = $errorCode;
        }
        
        public function GetHeaderString()
        {
            return 'HTTP/1.1 ' . $this->StatusCode . ' ' . $this->Name;
        }
    }
?>
