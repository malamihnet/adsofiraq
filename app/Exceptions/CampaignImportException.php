<?php

namespace App\Exceptions;

use Exception;

class CampaignImportException extends Exception
{
    public static function invalidUrl(): self
    {
        return new self('Please enter a valid campaign URL (http or https).');
    }

    public static function fetchFailed(?string $detail = null): self
    {
        $message = 'Could not fetch that URL. The site may be blocking requests or the link may be offline.';

        if ($detail) {
            $message .= ' '.$detail;
        }

        return new self($message);
    }

    public static function unsupportedPage(): self
    {
        return new self('This page does not look like a supported campaign page. Try an Ads of the World campaign URL.');
    }

    public static function noMetadata(): self
    {
        return new self('No usable campaign metadata was found on that page.');
    }

    public static function alreadyImported(): self
    {
        return new self('This campaign was already imported.');
    }
}
