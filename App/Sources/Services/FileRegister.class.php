<?php
class FileRegister
{
    private $MySql;
    
    public function __construct(mysqli $mySqli)
    {
        $this->MySql = $mySqli;
    }
}
?>
