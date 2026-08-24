<?php

namespace App\Enums;

enum DocumentSource: string
{
    case App = 'app';
    case UploadLink = 'upload_link';
    case Reprocess = 'reprocess';
}
