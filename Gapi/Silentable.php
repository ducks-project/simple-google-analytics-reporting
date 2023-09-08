<?php

namespace Ducks\Component\SimpleGoogleAnalyticsReporting\Gapi;

interface Silentable
{
    public function isSilent(): bool;
    public function setSilent(bool $silent): self;
}
