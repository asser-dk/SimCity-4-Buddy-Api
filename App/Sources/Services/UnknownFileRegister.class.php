<?php

class UnknownFileRegister
{

    private $MySql;

    function __construct(mysqli $mySqli)
    {
        $this->MySql = $mySqli;
    }

    public function AddFile(UnknownFile $file, string $pluginId)
    {
        $statement = $this->MySql->prepare(
            'INSERT INTO `UnknownFile` (`Id`, `Checksum`, `Filename`, `Plugin`) VALUE (?, ?, ?, ?)');
        $statement->bind_param(
            'ssss',
            strtoupper(Guid::NewGuid()),
            $file->Checksum,
            $file->Filename,
            strtoupper($pluginId));
        $statement->execute();
        $statement->close();
    }

    public function GetFilesForPlugin(string $pluginId)
    {
        $statement = $this->MySql->prepare(
            'SELECT
                `UnknownFile`.`Id` AS `id`,
                `UnknownFile`.`Filename` AS `filename`,
                `UnknownFile`.`Checksum` AS `checksum`,
                `UnknownFile`.`Plugin` AS `plugin`
            FROM `UnknownFile`
            WHERE `UnknownFile`.`Plugin` = ?');
        $statement->bind_param('s', strtoupper($pluginId));
        $statement->execute();
        $statement->bind_result($id, $filename, $checksum, $plugin);

        $files = [];

        while ($statement->fetch())
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

    public function TryGetPluginId(array $files)
    {
        $statement = $this->MySql->prepare(
            'SELECT `Plugin` FROM `UnknownFile` WHERE `Filename` = ? AND `Checksum` = ?');

        $pluginIdOutput = null;
        foreach ($files as $file)
        {
            $statement->bind_param('ss', $file->Filename, $file->Checksum);
            $statement->execute();
            $statement->bind_result($pluginId);
            $statement->close();

            if ($pluginId == null)
            {
                return null;
            }
            else if ($pluginIdOutput == null)
            {
                $pluginIdOutput = $pluginId;
            }
            else if ($pluginIdOutput != $pluginId)
            {
                return null;
            }
        }

        return $pluginIdOutput;
    }
}
