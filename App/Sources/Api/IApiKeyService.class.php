<?php
    interface IApiKeyService
    {
        function ValidateApiKey(string $apiKey, string $requiredPrivilege);
        
        function GetApiKey(string $apiKey);
        
        function DeleteApiKey(string $apiKey);
    }
?>
