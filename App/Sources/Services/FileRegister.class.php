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
            $file->Id = strtoupper($id);
            $file->Checksum = $checksum;
            $file->Filename = $filename;
            $file->Plugin = strtoupper($pluginId);
            $files[] = $file;
        }

        $statement->close();

        return $files;
    }

    public function AddFile(File $file)
    {
        $statement = $this->MySql->prepare('
            INSERT INTO `File` (`Id`, `Checksum`, `Filename`, `Plugin`) VALUE (?, ?, ?, ?)');
        $statement->bind_param('ssss', strtoupper($file->Id), $file->Checksum, $file->Filename, strtoupper($file->Plugin));
        $statement->execute();
    }

    public function HasFiles(string $pluginId)
    {
        $statement = $this->MySql->prepare('
            SELECT `File`.`Id` AS `id`
            FROM `File`
            WHERE `File`.`Plugin` = ?
            LIMIT 1');
        $statement->bind_param('s', strtoupper($pluginId));
        $statement->execute();
        $statement->bind_result($id);
        $statement->fetch();
        $statement->close();

        return $id !== null;
    }

    public function GetFilesForPlugin(string $pluginId)
    {
        $statement = $this->MySql->prepare('
            SELECT
                `File`.`Id` AS `id`,
                `File`.`Filename` AS `filename`,
                `File`.`Checksum` AS `checksum`,
                `File`.`Plugin` AS `plugin`
            FROM `File`
            WHERE `File`.`Plugin` = ?');
        $statement->bind_param('s', strtoupper($pluginId));
        $statement->execute();
        $statement->bind_result($id, $filename, $checksum, $plugin);

        $files = [];

        while($statement->fetch())
        {
            $file = new File();
            $file->Id = strtoupper($id);
            $file->Filename = $filename;
            $file->Checksum = $checksum;
            $file->Plugin = strtoupper($plugin);
            $files[] = $file;
        }

        $statement->close();

        return $files;
    }

    public function UpdateFile(File $file)
    {
        $statement = $this->MySql->prepare('
            UPDATE `File`
            SET
                `File`.`Filename` = ?,
                `File`.`Checksum` = ?,
                `File`.`Plugin` = ?
                WHERE `File`.`Id` = ?');
        $statement->bind_param('ssss', $file->Filename, $file->Checksum, strtoupper($file->Plugin), strtoupper($file->Id));
        $statement->execute();
    }

    public function RemoveFile(File $file)
    {
        $statement = $this->MySql->prepare('
            DELETE FROM `File`
            WHERE `File`.`Id` = ?');
        $statement->bind_param('s', strtoupper($file->Id));
        $statement->execute();
    }
}
?>
