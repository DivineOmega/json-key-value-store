<?php

namespace DivineOmega\JsonKeyValueStore;

class JsonKeyValueStore
{
    private $file;
    private $content;

    public function __construct($file)
    {
        $this->file = $file;
        $this->load();
    }

    private function createEmptyStore()
    {
        $this->content = new \stdClass();
        $this->save();
    }

    private function load()
    {
        if (!file_exists($this->file)) {
            $this->createEmptyStore();
        }

        $fh = fopen($this->file, 'r+');

        if (flock($fh, LOCK_SH)) {
            $rawContent = fread($fh, filesize($this->file));
            fflush($fh);
            flock($fh, LOCK_UN);
            fclose($fh);
        } else {
            throw new \Exception('Could not acquire shared file lock.');
        }

        $this->content = json_decode(gzdecode($rawContent));

        if ($this->content === null) {
            throw new Exception('Invalid store content');
        }
    }

    private function save()
    {
        $encodedContent = gzencode(json_encode($this->content));

        if (!file_exists($this->file)) {
            touch($this->file);
        }

        $fh = fopen($this->file, 'r+');

        if (flock($fh, LOCK_EX)) {
            ftruncate($fh, 0);
            fwrite($fh, $encodedContent);
            fflush($fh);
            flock($fh, LOCK_UN);
            fclose($fh);
        } else {
            throw new \Exception('Could not acquire exclusive file lock.');
        }
    }

    public function getFile()
    {
        return $this->file;
    }

    public function set($key, $value)
    {
        $this->content->$key = $value;
        $this->save();
    }

    public function get($key)
    {
        if (!isset($this->content->$key)) {
            return;
        }

        return $this->content->$key;
    }

    public function delete($key)
    {
        unset($this->content->$key);
    }
}
