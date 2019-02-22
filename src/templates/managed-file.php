<?php

use FTRatings\ManagedFile;

global $params;
ManagedFile::fromPath($params['file'])->transfer();