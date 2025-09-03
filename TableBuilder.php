<?php

class TableBuilder
{
    private $sql = [];
    private $table = '';

    public function create($table, $callback)
    {
        $this->table = $table;
        $callback($this);
        $sql = "CREATE TABLE `$table` (\n" . implode(",\n", $this->sql) . "\n);";
        $this->reset();
        return $sql;
    }

    public function update($table, $callback)
    {
        $this->table = $table;
        $callback($this);
        $sql = "ALTER TABLE `$table`\n" . implode(",\n", $this->sql) . ";";
        $this->reset();
        return $sql;
    }

    // Column definitions
    public function id()
    {
        $this->sql[] = "  id INT AUTO_INCREMENT PRIMARY KEY";
    }

    public function string($name, $length = 255, $default = null)
    {
        $col = "  `$name` VARCHAR($length)";
        if (!is_null($default)) {
            $col .= " DEFAULT '" . addslashes($default) . "'";
        }
        $this->sql[] = $col;
    }

    public function integer($name, $default = null)
    {
        $col = "  `$name` INT";
        if (!is_null($default)) {
            $col .= " DEFAULT " . intval($default);
        }
        $this->sql[] = $col;
    }

    public function boolean($name, $default = null)
    {
        $col = "  `$name` TINYINT(1)";
        if (!is_null($default)) {
            $col .= " DEFAULT " . ($default ? 1 : 0);
        }
        $this->sql[] = $col;
    }

    public function timestamps()
    {
        $this->sql[] = "  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
        $this->sql[] = "  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
    }

    public function addColumn($name, $type, $default = null, $length = null)
    {
        $col = "  ADD COLUMN `$name` ";
        if (strtolower($type) === 'boolean') {
            $col .= "TINYINT(1)";
            if (!is_null($default)) {
                $col .= " DEFAULT " . ($default ? 1 : 0);
            }
        } elseif ($length) {
            $col .= strtoupper($type) . "($length)";
            if (!is_null($default)) {
                $col .= " DEFAULT '" . addslashes($default) . "'";
            }
        } else {
            $col .= strtoupper($type);
            if (!is_null($default)) {
                $col .= " DEFAULT '" . addslashes($default) . "'";
            }
        }
        $this->sql[] = $col;
    }

    public function dropColumn($name)
    {
        $this->sql[] = "  DROP COLUMN `$name`";
    }

    public function unique($name)
    {
        $this->sql[] = "  UNIQUE (`$name`)";
    }

    private function reset()
    {
        $this->sql = [];
        $this->table = '';
    }
}
