<?php
    class DateTimeUtil
    {
        const ISO8601 = '/^\d{4}.\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{4}$/';

        const MySqlDateTime = 'Y-m-d H:i:s';

        public static function IsValid (string $dateTimeString, string $regexPattern)
        {
            return preg_match($regexPattern, $dateTimeString) === 1;
        }
    }
?>
