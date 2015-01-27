<?php
    
    class MySql
    {
        private $Configuration;
        
        public function __construct(IConfiguration $configuration)
        {
            $this->Configuration = $configuration;
        }
        
        public function Connect()
        {
            $mysqli = mysqli_init();
            
            if(!$mysqli)
            {
                throw new Exception('mysqli_init failed.');
            }
            
            if(!$mysqli->options(MYSQLI_INIT_COMMAND, 'SET AUTOCOMMIT = 0'))
            {
                throw new Exception('Setting MYSQLI_INIT_COMMAND failed.');
            }
            
            if(!$mysqli->real_connect(
                $this->Configuration->GetMySql()->Host,
                $this->Configuration->GetMySql()->Username,
                $this->Configuration->GetMySql()->Password,
                $this->Configuration->GetMySql()->DatabaseName,
                $this->Configuration->GetMySql()->Port))
            {
                throw new Exception('Connection error ('.mysqli_errno().') ' . mysqli_error());
            }
            
            return $mysqli;
        }
    }
?>
