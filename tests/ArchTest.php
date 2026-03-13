<?php

declare(strict_types=1);
use Illuminate\Console\Command;

arch('all source files use strict types')
    ->expect('DavitVardanyan\NetdataLaravel')
    ->toUseStrictTypes();

arch('all classes are final')
    ->expect('DavitVardanyan\NetdataLaravel')
    ->toBeFinal()
    ->ignoring('DavitVardanyan\NetdataLaravel\Tests')
    ->ignoring('DavitVardanyan\NetdataLaravel\Testing')
    ->ignoring('DavitVardanyan\NetdataLaravel\Health\Status');

arch('no debugging statements')
    ->expect('DavitVardanyan\NetdataLaravel')
    ->not->toUse(['dd', 'dump', 'ray', 'var_dump', 'print_r']);

arch('events are readonly')
    ->expect('DavitVardanyan\NetdataLaravel\Events')
    ->toBeReadonly();

arch('commands extend Command')
    ->expect('DavitVardanyan\NetdataLaravel\Commands')
    ->toExtend(Command::class);

arch('no framework code leaks into events')
    ->expect('DavitVardanyan\NetdataLaravel\Events')
    ->not->toUse(['Illuminate\Support\Facades']);
