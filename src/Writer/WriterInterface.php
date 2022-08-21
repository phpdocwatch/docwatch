<?php

namespace DocWatch\DocWatch\Writer;

use DocWatch\DocWatch\Docs;

interface WriterInterface
{
    /**
     * Open the file stream if the Write requires a stream
     *
     * @param string $path
     * @param Docs $docs
     * @return void
     */
    public function open(string $path, Docs $docs);

    /**
     * Write all docs to the given path
     *
     * @param string $path
     * @param Docs $docs
     * @return void
     */
    public function write(string $path, Docs $docs);

    /**
     * Close the file stream if previously opened.
     *
     * @param string $path
     * @param Docs $docs
     * @return void
     */
    public function close(string $path, Docs $docs);
}
