<?php

include_once('/home/bitrix/www/crest/1/cextrest.php');
require_once (__DIR__.'/settings.php');

class overCRest extends CRestExt {
    
}

overCRest::setTable(TABLE_NAME);