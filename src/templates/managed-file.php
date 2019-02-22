<?php

use WP_Private_Files\ManagedFile;

global $params;
ManagedFile::fromPath($params['file'])->transfer();
