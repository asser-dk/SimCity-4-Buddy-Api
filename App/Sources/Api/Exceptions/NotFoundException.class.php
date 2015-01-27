<?php
    class NotFoundException extends HttpStatusException
    {
        public function __construct(integer $errorCode, string $message)
        {
            parent::__construct(404, 'Not Found', $errorCode, $message);
        }
    }
?>
