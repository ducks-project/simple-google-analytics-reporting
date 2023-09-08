<?php

namespace Ducks\Component\SimpleGoogleAnalyticsReporting\Gapi\Traits;

trait Silent
{
    private bool $silent = true;

    public function isSilent(): bool
    {
        return $this->silent;
    }

    public function setSilent(bool $silent)
    {
        $this->silent = $silent;

        return $this;
    }
}
