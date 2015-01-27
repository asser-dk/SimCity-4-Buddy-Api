<?php 
    class Guid
    {
        public static function NewGuid()
        {
            if (function_exists('com_create_guid') === true)
            {
                return trim(com_create_guid(), '{}');
            }

            return sprintf(
                '%04X%04X-%04X-%04X-%04X-%04X%04X%04X', 
                mt_rand(0, 65535), 
                mt_rand(0, 65535), 
                mt_rand(0, 65535), 
                mt_rand(16384, 20479), 
                mt_rand(32768, 49151), 
                mt_rand(0, 65535), 
                mt_rand(0, 65535), 
                mt_rand(0, 65535));
        }
        
        public static function IsValid(string $guid)
        {
            if (preg_match('/^\{?[A-z0-9]{8}-[A-z0-9]{4}-[A-z0-9]{4}-[A-z0-9]{4}-[A-z0-9]{12}\}?$/', $guid)) {
                return true;
            } else {
                return false;
            }
        }
    }
    
?>
