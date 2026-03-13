<?php

declare(strict_types=1);

namespace DavitVardanyan\NetdataLaravel\Health;

enum Status: string
{
    case Ok = 'ok';
    case Warning = 'warning';
    case Failed = 'failed';
}
