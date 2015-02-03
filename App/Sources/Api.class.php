<?php
    class Api
    {
        public $Controllers;

        private $Uri;

        private $Method;

        private $Path;

        private $ApiKey;

        private $ApiKeyService;

        public function __construct(array $serverArguments, array $controllers, IApiKeyService $apiKeyService)
        {
            $uriParts = parse_url($serverArguments['REQUEST_URI']);
            $this->Uri = $serverArguments['REQUEST_URI'];
            $this->Method = $serverArguments['REQUEST_METHOD'];
            $this->Path = rtrim($uriParts['path'], '/');

            $this->ApiKey = $this->ExtractApiKeyFromQuery($uriParts['query']);
            $this->Controllers = $controllers;
            $this->ApiKeyService = $apiKeyService;
        }

        public static function Main()
        {
            Typehint::initializeHandler();

            date_default_timezone_set('UTC');
            header("Cache-Control: no-cache, must-revalidate");
            header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

            ob_start();
            ob_start('ob_gzhandler');

            $configuration = new Configuration(CONFIGURATION_FILE);
            $mysql = new MySql($configuration);
            $mysqli = $mysql->Connect();

            $apiKeyService = new ApiKeyService($mysqli);

            $fileRegister = new FileRegister($mysqli);
            $pluginRegister = new PluginRegister($mysqli);

            try
            {
                $controllers = array(
                    new FileController($fileRegister, $pluginRegister),
                    new PluginController($pluginRegister)
                );

                $api = new Api($_SERVER, $controllers, $apiKeyService);

                $resource = $api->ProcessResource();

                echo json_encode($resource, JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);
            }
            catch(HttpStatusException $ex)
            {
                echo json_encode(self::GenerateErrorResource($ex), JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);
            }
            catch(Exception $ex)
            {
                echo json_encode(self::GenerateInternalError($ex), JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);
            }
            finally
            {
                ob_end_flush();
                $mysqli->close();
                header('Content-Length: ' . ob_get_length());
                ob_end_flush();
            }
        }

        public static function GenerateErrorResource(HttpStatusException $exception)
        {
            header($exception->GetHeaderString());

            return array(
                'Timestamp' => date('c'),
                    'Status' => array(
                        'Code' => $exception->StatusCode,
                        'Name' => $exception->Name
                    ),
                    'Error' => array(
                        'Code' => $exception->ErrorCode,
                        'Message' => $exception->getMessage()
                    )
            );
        }

        public static function GenerateInternalError(Exception $ex)
        {
            header('HTTP/1.1 500 Internal Server Error');

            $error = new Error();
            $error->Code = GeneralError::InternalError;
            $error->Message = 'An internal server error has occurred.';
            $error->Extended = $ex->getMessage();

            return
                array(
                    'Timestamp' => date('c'),
                    'Status' => array(
                        'Code' => 500,
                        'Name' => 'Internal Server Error'
                    ),
                    'Error' => $error
                );
        }

        public function ExtractApiKeyFromQuery(string $query = null)
        {
            if(isset($query))
            {
                $query = strtolower($query);

                $params = array();
                $paramStrings = explode('&', strtolower($query));
                foreach($paramStrings as $param)
                {
                    list($key, $value) = explode('=', $param, 2);
                    $params[$key] = $value;
                }

                if(array_key_exists('apikey', $params))
                {
                    return $params['apikey'];
                }
            }

            return null;
        }

        public function ProcessResource()
        {
            header('Access-Control-Allow-Origin: *');
            header('Content-Type: application/json');

            $routeTable = $this->GetAllRouteTables($this->Controllers);

            if($this->Path === '')
            {
                header('HTTP/1.1 200 OK');

                return $this->GetAllRouteEndpoints($routeTable);
            }

            foreach($routeTable as $key=>$route)
            {
                if(preg_match($route['regex'], $this->Path) < 1 || !array_key_exists($this->Method, $route['methods']))
                {
                    continue;
                }

                $routeMethod = $route['methods'][$this->Method];

                if(isset($routeMethod['authentication']))
                {
                    $this->ValidateApiKey($routeMethod['authentication']);
                }

                $arguments = $this->ExtractArguments($route['arguments']);

                $controller = $route['controller'];

                return $controller->ProcessRequest($routeMethod['method'], $arguments);
            }

            throw new NotFoundException(GeneralError::ResourceNotFound, 'The requested resource \'' . $this->Path . '\' was not found on this server.');
        }

        public function ValidateApiKey(string $requiredPrivilege)
        {
            if($this->ApiKey === null)
            {
                throw new ForbiddenException(GeneralError::MissingApiKey, 'Api key is missing.');
            }

            $this->ApiKeyService->ValidateApiKey($this->ApiKey, $requiredPrivilege);
        }

        public function ExtractArguments(array $argumentsRegex = null)
        {
            $arguments = array();

            if($argumentsRegex !== null)
            {
                foreach($argumentsRegex as $key => $regex)
                {
                    preg_match($regex['pattern'], $this->Uri, $matches);
                    $arguments[$key] = $matches[$regex['index']];
                }
            }

            if($this->Method === 'POST')
            {
                foreach($_POST as $postKey=>$postValue)
                {
                    $arguments['post'][$postKey] = $postValue;
                }
            }

            $rawRequestBody = Request::GetPayload();

            if($rawRequestBody !== null && $rawRequestBody !== '')
            {
                $requestBody = json_decode($rawRequestBody, true);

                if($requestBody == null)
                {
                    throw new BadRequestException(GeneralError::InvalidJson, 'Request body must be valid JSON.');
                }

                $arguments['payload'] = $requestBody;
            }

            return $arguments;
        }

        public function GetAllRouteEndpoints(array $routeTable)
        {
            $routes = array();

            foreach($routeTable as $key=>$route)
            {
                if (is_array($routes) && is_array($route['documentation']))
                {
                    $routes = array_merge($routes, $route['documentation']);
                }
            }

            ksort($routes);

            return $routes;
        }

        public function GetAllRouteTables(array $controllers)
        {
            $routeTables = array();

            foreach($controllers as $controller){
                $table = $controller->RouteTable();

                $routeTables = array_merge($routeTables, $table);
            }

            return $routeTables;
        }
    }
?>
