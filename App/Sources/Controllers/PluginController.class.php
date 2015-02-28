<?php
class PluginController extends BaseController
{

    const MaxPluginsPerPage = 100;

    private $Register;

    public function __construct(PluginRegister $register)
    {
        $this->Register = $register;
    }

    public function RouteTable()
    {
        return [
            'plugin' => [
                'methods' => [
                    'GET' => ['method' => 'GetPlugin'],
                    'PUT' => ['method' => 'PutPlugin', 'authentication' => 'PutPlugin']
                ],
                'controller' => $this,
                'documentation' => ['/plugins/{pluginId}' => 'Get info on a specific plugin'],
                'regex' => '/^\/plugins\/[A-z0-9-]{36}$/',
                'arguments' => [
                    'pluginId' => [
                        'pattern' => '/^\/plugins\/([A-z0-9-]{36})/',
                        'index' => 1
                    ]
                ]
            ],
            'plugins' => [
                'methods' => [
                    'POST' => ['method' => 'PostPlugin', 'authentication' => 'PostPlugin'],
                    'GET' => ['method' => 'GetPlugins']
                ],
                'controller' => $this,
                'documentation' => ['/plugins' => 'Lists all plugins'],
                'arguments' => PaginationHelper::GetRoutePaginationArguments(),
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
            case 'PutPlugin':
                return $this->PutPlugin($arguments['pluginId'], $arguments['payload']);
            case 'GetPlugins':
                return $this->GetPlugins((int)$arguments['page'], (int)$arguments['perPage'], $arguments['orderBy']);
            default:
                throw new NotFoundException(
                    GeneralError::ResourceNotFound,
                    'The requested resource was not found on this server.');
        }
    }

    public function GetPlugins(integer $page, integer $perPage, string $orderBy)
    {
        PaginationHelper::ValidateAndSetPage($page);
        PaginationHelper::ValidateAndSetPerPage($perPage, self::MaxPluginsPerPage);

        $map = [
            'name' => '`Plugin`.`Name`',
            'auithor' => '`Plugin`.`Author`'];

        $orderByString = PaginationHelper::ValidateAndGenerateOrderByString($orderBy, $map, 'name');

        $plugins = $this->Register->GetPlugins($page, $perPage, $orderByString);

        if (count($plugins) > 0)
        {
            header('HTTP/1.1 200 OK');
        }
        else
        {
            header('HTTP/1.1 404 Not Found');
        }

        return $plugins;
    }

    public function PutPlugin(string $pluginId, array $rawPlugin = null)
    {
        self::ThrowErrorOnInvalidGuid($pluginId, 'Plugin id is malformed.');

        $oldPlugin = $this->Register->GetPlugin($pluginId);

        if ($oldPlugin === null)
        {
            throw new NotFoundException(
                GeneralError::ResourceNotFound,
                'No plugin with the id ' . $pluginId . ' found.');
        }

        self::ThrowErrorOnEmptyPayload($rawPlugin, 'Request payload is malformed.');
        self::ThrowErrorOnNullOrEmptyString($rawPlugin['Name'], 'Plugin name is missing.');
        self::ThrowErrorOnNullOrEmptyString($rawPlugin['Author'], 'Author name is missing.');
        self::ThrowErrorOnNullOrEmptyString($rawPlugin['Link'], 'Link is missing.');
        self::ThrowErrorOnNullOrEmptyString($rawPlugin['Description'], 'Description is missing.');
        self::ThrowErrorOnNullOrEmptyString($rawPlugin['Version'], 'Version is missing.');

        if (preg_match(
                '/^(?:(?:https?|ftp):\/\/)(?:www\.)?[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i',
                $rawPlugin['Link']) == FALSE
        )
        {
            throw new BadRequestException(GeneralError::InvalidParameter, 'Link is not a valid URL.');
        }

        $plugin = new Plugin();
        $plugin->Id = $pluginId;
        $plugin->Name = $rawPlugin['Name'];
        $plugin->Author = $rawPlugin['Author'];
        $plugin->Description = $rawPlugin['Description'];
        $plugin->Link = $rawPlugin['Link'];
        $plugin->Version = $rawPlugin['Version'];

        if (isset($rawPlugin['Dependencies']) && is_array($rawPlugin['Dependencies']))
        {
            $dependencies = array_unique($rawPlugin['Dependencies']);

            foreach ($dependencies as $dependency)
            {
                self::ThrowErrorOnInvalidGuid($dependency, 'Dependency plugin id ' . $dependency . ' is invalid.');
                if ($this->Register->GetPlugin($dependency) == null)
                {
                    throw new BadRequestException(
                        GeneralError::ResourceNotFound,
                        'Dependency plugin id ' . $dependency . ' was not found.');
                }
            }

            $plugin->Dependencies = $dependencies;
        }

        $updatedPlugin = $this->Register->UpdatePlugin($plugin);

        header('HTTP/1.1 200 OK');
        return $updatedPlugin;
    }

    public function PostPlugin(array $rawPlugin = null)
    {
        self::ThrowErrorOnEmptyPayload($rawPlugin, 'Request payload is malformed.');
        self::ThrowErrorOnNullOrEmptyString($rawPlugin['Name'], 'Plugin name is missing.');
        self::ThrowErrorOnNullOrEmptyString($rawPlugin['Author'], 'Author name is missing.');
        self::ThrowErrorOnNullOrEmptyString($rawPlugin['Link'], 'Link is missing.');
        self::ThrowErrorOnNullOrEmptyString($rawPlugin['Description'], 'Description is missing.');
        self::ThrowErrorOnNullOrEmptyString($rawPlugin['Version'], 'Version is missing.');

        if (preg_match(
                '/^(?:(?:https?|ftp):\/\/)(?:www\.)?[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i',
                $rawPlugin['Link']) == FALSE
        )
        {
            throw new BadRequestException(GeneralError::InvalidParameter, 'Link is not a valid URL.');
        }

        $plugin = new Plugin();
        $plugin->Id = Guid::NewGuid();
        $plugin->Name = $rawPlugin['Name'];
        $plugin->Link = $rawPlugin['Link'];
        $plugin->Author = $rawPlugin['Author'];
        $plugin->Description = $rawPlugin['Description'];
        $plugin->Version = $rawPlugin['Version'];

        if (isset($rawPlugin['Dependencies']) && is_array($rawPlugin['Dependencies']))
        {
            $dependencies = array_unique($rawPlugin['Dependencies']);

            foreach ($dependencies as $dependency)
            {
                self::ThrowErrorOnInvalidGuid($dependency, 'Dependency plugin id ' . $dependency . ' is invalid.');
                if ($this->Register->GetPlugin($dependency) == null)
                {
                    throw new BadRequestException(
                        GeneralError::ResourceNotFound,
                        'Dependency plugin id ' . $dependency . ' was not found.');
                }
            }

            $plugin->Dependencies = $dependencies;
        }

        $this->Register->AddPlugin($plugin);

        header('HTTP/1.1 201 Created');
        return $plugin;
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
}
