<?php 

interface IController
{
    public function RouteTable();
    
    public function ProcessRequest(string $memberName, array $arguments = null);
}

?>
