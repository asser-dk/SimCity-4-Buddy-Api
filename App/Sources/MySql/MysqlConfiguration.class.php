<?php
    class MysqlConfiguration
    {
        public $Host;

        public $Port;

        public $Username;

        public $Password;

        public $DatabaseName;

        public function __construct(array $mysqlConfiguration)
        {
            $this->Host = $mysqlConfiguration['host'];
            $this->Port = $mysqlConfiguration['port'];
            $this->Username = $mysqlConfiguration['username'];
            $this->Password = $mysqlConfiguration['password'];
            $this->DatabaseName = $mysqlConfiguration['databaseName'];
        }
    }
?>