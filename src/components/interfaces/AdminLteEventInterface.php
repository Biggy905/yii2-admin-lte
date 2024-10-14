<?php

namespace applications\adminlte\ViewComponent\components\interfaces;

interface AdminLteEventInterface
{
    const EVENT_HEAD = 'head';
    const EVENT_BODY_BEFORE = 'beforeBody';
    const EVENT_BODY_AFTER = 'afterBody';
}
