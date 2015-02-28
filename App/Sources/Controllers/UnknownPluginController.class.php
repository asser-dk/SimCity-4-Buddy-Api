<?php

class UnknownPluginController extends BaseController
{
    private $Register;

    private $FileRegister;

    public function __construct(UnknownPluginRegister $register, UnknownFileRegister $fileRegister)
    {
        $this->Register = $register;
        $this->FileRegister = $fileRegister;
    }

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
        self::ThrowErrorOnInvalidGuid($pluginId, 'Plugin id is malformed.');

        $plugin = $this->Register->GetPlugin($pluginId);

        if ($plugin === null)
        {
            header('HTTP/1.1 404 Not Found');
        }
        else
        {
            header('HTTP/1.1 200 OK');
        }

        return $plugin;
    }

    public function PostPlugin(array $rawPlugin)
    {
        self::ThrowErrorOnEmptyPayload($rawPlugin, 'Request payload is malformed.');
        self::ThrowErrorOnNullOrEmptyString($rawPlugin['Link'], 'Link is missing.');
        self::ThrowErrorOnNullOrEmptyArray($rawPlugin['Files'], 'No files are specified.');

        if (preg_match(
                '/^(?:(?:https?|ftp):\/\/)(?:www\.)?[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i',
                $rawPlugin['Link']) == FALSE
        )
        {
            throw new BadRequestException(GeneralError::InvalidParameter, 'Link is not a valid URL.');
        }

        $plugin = new UnknownPlugin();
        $plugin->Link = $rawPlugin['Link'];

        foreach ($rawPlugin['Files'] as $rawFile)
        {
            self::ThrowErrorOnNullOrEmptyString($rawFile['Checksum'], 'Checksum is missing.');
            self::ThrowErrorOnNullOrEmptyString($rawFile['Filename'], 'Filename is missing.');

            $file = new UnknownFile();
            $file->Filename = $rawFile['Filename'];
            $file->Checksum = $rawFile['Checksum'];

            $plugin->Files[] = $file;
        }

        $pluginIdFromLink = $this->Register->TryGetPluginId($plugin);
        $pluginIdFromFiles = $this->FileRegister->TryGetPluginId($plugin->Files);

        if ($pluginIdFromLink === null && $pluginIdFromFiles === null)
        {
            $this->Register->AddPlugin($plugin);

            header('HTTP/1.1 201 Created');
            return $plugin;
        }
        else if ($pluginIdFromLink == $pluginIdFromFiles)
        {
            $this->Register->IncrementSubmissions($pluginIdFromLink);
            header('HTTP/1.1 204 No Content');
            return null;
        }
        else if ($pluginIdFromFiles != null)
        {
            $this->Register->AddPlugin($plugin);

            header('HTTP/1.1 201 Created');
            return $plugin;
        }
    }
}
