<?php
class PluginController extends BaseController
{
    private $Register;

    public function __construct(PluginRegister $register)
    {
        $this->Register = $register;
    }

    public function RouteTable()
    {
        return array(
            'plugin' => array(
                'methods' => array(
                    'GET' => array('method' => 'GetPlugin')
                ),
                'controller' => $this,
                'documentation' => array('/plugins/{pluginId]' => 'Get info on a specific plugin'),
                'regex' => '/^\/plugins\/[A-z0-9-]{36}$/',
                'arguments' => array(
                    'pluginId' => array(
                        'pattern' => '/^\/plugins\/([A-z0-9-]{36})/',
                        'index' => 1
                    )
                )
            ),
            'plugins' => array(
                'methods' => array(
                    'POST' => array('method' => 'PostPlugin', 'authentication' => 'PostPlugin')
                ),
                'controller' => $this,
                'regex' => '/^\/plugins$/'
            )
        );
    }

    public function ProcessRequest(string $memberName, array $arguments = null)
    {
        switch($memberName)
        {
            case 'GetPlugin':
                return $this->GetPlugin($arguments['pluginId']);
            case 'PostPlugin':
                return $this->PostPlugin($arguments['payload']);
            default:
                throw new NotFoundException(GeneralError::ResourceNotFound, 'The requested resource was not found on this server.');
        }
    }

    public function PostPlugin(array $rawPlugin = null)
    {
        self::ThrowErrorOnEmptyPayload($rawPlugin);

        self::ThrowErrorOnNullOrEmptyString($rawPlugin['Name'], 'Plugin name is missing');
        self::ThrowErrorOnNullOrEmptyString($rawPlugin['Author'], 'Plugin author is missing');
        self::ThrowErrorOnNullOrEmptyString($rawPlugin['Link'], 'Plugin link is missing');
        self::ThrowErrorOnNullOrEmptyString($rawPlugin['Description'], 'Plugin description is missing.');

        if(preg_match('/^(?:(?:https?|ftp):\/\/)(?:www\.)?[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i', $rawPlugin['Link']) == FALSE)
        {
            throw new BadRequestException(GeneralError::InvalidParameter, 'Link is not a valid URL.');
        }

        if($this->Register->IsUrlInUse($rawPlugin['Link']))
        {
            throw new BadRequestException(GeneralError::UniqueValueAlreadyTaken, 'There is already a plugin registered for this URL.');
        }

        $plugin = new Plugin();
        $plugin->Id = Guid::NewGuid();
        $plugin->Name = $rawPlugin['Name'];
        $plugin->Link = $rawPlugin['Link'];
        $plugin->Author = $rawPlugin['Author'];
        $plugin->Description = $rawPlugin['Description'];

        $this->Register->AddPlugin($plugin);

        header('HTTP/1.1 201 Created');
        return $plugin;
    }

    public function GetPlugin(string $pluginId)
    {
        self::ThrowErrorOnInvalidGuid($pluginId, 'Plugin id is malformed.');

        $plugin = $this->Register->GetPlugin($pluginId);

        if($plugin === null)
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
?>
