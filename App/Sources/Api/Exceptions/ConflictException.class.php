<?php
    class ConflictException extends HttpStatusException
    {
        public function __construct(integer $errorCode, string $message)
        {
            parent::__construct(409, 'Conflict', $errorCode, $message);
        }
    }
?>
