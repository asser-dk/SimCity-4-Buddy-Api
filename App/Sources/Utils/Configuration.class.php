<?php
    class Configuration implements IConfiguration
    {
        private $mySql;

        private $dateTimeFormat;

        private $dateFormat;

        private $storageDateFormat;

        public function GetMySql()
        {
            return $this->mySql;
        }

        public function GetDateTimeFormat()
        {
            return $this->dateTimeFormat;
        }

        public function GetDateFormat()
        {
            return $this->dateFormat;
        }

        public function GetStorageDateFormat()
        {
            return $this->storageDateFormat;
        }

        public function __construct(string $filename)
        {
            $configuration = parse_ini_file($filename, true);

            $this->mySql = new MysqlConfiguration($configuration['mysql']);

            $this->dateTimeFormat = DateTime::ISO8601;
            $this->dateFormat = 'o-m-d';
            $this->storageDateFormat = 'Y-m-d H:i:s';
        }
    }
?>
