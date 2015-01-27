<?php
class FileRegister
{
    private $MySql;

    public function __construct(mysqli $mySqli)
    {
        $this->MySql = $mySqli;
    }

    public function GetAllFiles(integer $page, integer $perPage, string $orderBy)
    {
        $offset = PaginationHelper::CalculateOffset($page, $perPage);

        $statementString = '
            SELECT
                `File`.`Id` AS `id`,
                `File`.`Checksum` AS `checksum`,
                `File`.`Filename` AS `filename`,
                `File`.`Plugin` AS `pluginId`
            FROM `File`
            ORDER BY ' . $orderBy . '
            LIMIT ?, ?';

        $statement = $this->MySql->prepare($statementString);
        $statement->bind_param('ii', $offset, $perPage);
        $statement->execute();
        $statement->bind_result($id, $checksum, $filename, $pluginId);

        $files = [];

        while($statement->fetch())
        {
            $file = new File();
            $file->Id = $id;
            $file->Checksum = $checksum;
            $file->Filename = $filename;
            $file->Plugin = $pluginId;
            $files[] = $file;
        }

        $statement->close();

        return $files;
    }
}
?>
