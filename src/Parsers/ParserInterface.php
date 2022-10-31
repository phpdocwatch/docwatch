<?php

namespace DocWatch\Parsers;

use DocWatch\Doc;
use DocWatch\Docs;
use DocWatch\File;

interface ParserInterface
{
    public function withConfig(array $config);

    public function withDocs(Docs $docs);

    public function parse(File $file): Doc|Docs|null;

    public function standalone(): Doc|Docs|null;
}