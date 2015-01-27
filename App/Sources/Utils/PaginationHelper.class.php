<?php
    class PaginationHelper
    {
        public static function GetRoutePaginationArguments()
        {
            return
                array(
                    'page' => array(
                        'pattern' => '/page=([0-9]*)/',
                        'index' => 1
                    ),
                    'perPage' => array(
                        'pattern' => '/perPage=([0-9]*)/',
                        'index' => 1
                    ),
                    'orderBy' => array(
                        'pattern' => '/orderBy=([A-z.]*)/',
                        'index' => 1
                    )
                );
        }

        public static function CalculateOffset(integer $page, integer $perPage)
        {
            return ($page - 1 ) * $perPage;
        }

        public static function ValidateAndSetPerPage(integer &$perPage, integer $maxPerPage)
        {
            if($perPage < 0 || $perPage > $maxPerPage)
            {
                throw new InvalidArgumentException('Invalid perPage parameter value. Valid range is {1, '.$maxPerPage.'}. This parameter is optional.');
            }

            if($perPage === 0)
            {
                $perPage = $maxPerPage;
            }
        }

        public static function ValidateAndSetPage(integer &$page)
        {
            if($page < 0)
            {
                throw new InvalidArgumentException('Invalid page parameter value. Valid range is {1, n} (if you open a page with no items a \'404 Not Found\' error is returned).');
            }

            if($page === 0)
            {
                $page = 1;
            }
        }

        public static function ValidateAndGenerateOrderByString(string $orderBy, array $map, string $defaultOrderByField)
        {
            $orderByString = $map[$defaultOrderByField] . ' ASC';

            if($orderBy !== null)
            {
                $orderBy = strtolower($orderBy);
                if(strpos($orderBy, '.') === FALSE)
                {
                    $field = $orderBy;
                    $direction = 'ASC';
                }
                else
                {
                    $delimiter = strrpos($orderBy, '.');
                    $field = substr($orderBy, 0, $delimiter);

                    $direction = strtoupper(substr($orderBy, $delimiter + 1));

                    if($direction !== 'ASC' && $direction !== 'DESC')
                    {
                        self::ThrowInvalidOrderBy($map);
                    }
                }

                $match = false;

                foreach($map as $orderField => $sqlField)
                {
                    if($field === $orderField)
                    {
                        $orderByString = $sqlField . ' ' . $direction;
                        $match = true;
                    }
                }

                if(!$match)
                {
                    self::ThrowInvalidOrderBy($map);
                }
            }

            return $orderByString;
        }

        public static function ThrowInvalidOrderBy(array $map)
        {
            $validFields = 'Valid fields are';

            foreach($map as $field=>$sqlField)
            {
                $validFields .= ' \''.$field .'\',';
            }

            $validFields = substr($validFields, 0, strlen($validFields) - 1);

            throw new InvalidArgumentException('Invalid orderBy parameter value. Valid format is \'field.order\' i.e. \'name.asc\'. '.$validFields.'. Valid orders are \'asc\' or \'desc\'. This parameter is optional.');
        }
    }
?>
