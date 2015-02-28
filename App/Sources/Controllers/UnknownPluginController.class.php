<?php

class UnknownPluginController extends BaseController
{
    public function RouteTable()
    {
        return [
            'unknownPlugin' => [
                'methods' => [
                    'GET' => ['method' => 'GetPlugin']
                ],
                'controller' => $this,
                'documentation' => ['/plugins/unknown/{pluginId}' => 'Get info on a specific unknown plugin.'],
                'regex' => '/^\/plugins\/unknown\/[A-z0-9-]{36}$/',
                'arguments' => [
                    'pluginId' => [
                        'pattern' => '/^\/plugins\/unknown\/([A-z0-9-]{36})/',
                        'index' => 1
                    ]
                ]
            ],
            'unknownPlugins' =>
                [
                    'methods' => [
                        'POST' => ['method' => 'PostPlugin']
                    ],
                    'controller' => $this,
                    'regex' => '/^\/plugins$/'
                ]
        ];
    }

    public function ProcessRequest(string $memberName, array $arguments = null)
    {
        switch ($memberName)
        {
            case 'GetPlugin':
                return $this->GetPlugin($arguments['pluginId']);
            case 'PostPlugin':
                return $this->PostPlugin($arguments['payload']);
            default:
                throw new NotFoundException(
                    GeneralError::ResourceNotFound,
                    'The requested resource was not found on this server.');
        }
    }

    public function GetPlugin(string $pluginId)
    {
        throw new Exception('Not implemented');
    }

    public function PostPlugin(array $payload)
    {
        throw new Exception('Not Implemented');
    }
}
